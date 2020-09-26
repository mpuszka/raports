<?php

class AdminRaportsController extends ModuleAdminController 
{   
    /**
     * Id language context
     *
     * @var int
     */
    private $idLang;

    /**
     * Constructor
     */
    public function __construct() 
    {
        $this->bootstrap    = true;
        $this->idLang       = (int)Context::getContext()->language->id;
        parent::__construct();
    }

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void 
    {
        parent::init();
    }

    /**
     * Render list method
     *
     * @return string
     */
    public function renderList(): string
    {
        $form = $this->renderForm(); 
        
        $this->context
                ->smarty
                ->assign('form_tpl', $form); 

        return $this->context
                    ->smarty
                    ->fetch(_PS_MODULE_DIR_.'raports/views/templates/admin/raports/raports.tpl'); 
    }

    /**
     * Render form method
     *
     * @return string
     */
    public function renderForm(): string
    {       
        $this->display = 'edit';
        $this->initToolbar();

        $allOrderStates     = $this->getAllOrderStates();
        $optionsOrderStates = $this->generateSelectOrderState($allOrderStates);
        
        $this->fields_form = [
            'input' => [
                [
                    'type'      => 'date',
                    'label'     => $this->l('Start date'),
                    'name'      => 'start-date',
                    'required'  => true
                ],
                [
                    'type'      => 'date',
                    'label'     => $this->l('End date'),
                    'name'      => 'end-date',
                    'required'  => true
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Status'),
                    'name'      => 'status',
                    'required'  => true,
                    'multiple'  => false,
                    'options'   => [
                        'query' =>  $optionsOrderStates,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    
                ]
            ],
            'submit' => [
                'title' => $this->l('Generate'), 
                'class' => 'button', 
                'name'  => 'raportForm'
            ]
        ];
 
        return parent::renderForm();
    }

    /**
     * Pre process post after submit form methdo
     *
     * @return void
     */
    public function postProcess(): void
    {
        if (Tools::isSubmit('raportForm'))
        {
            $startDate  = strval(Tools::getValue('start-date'));
            $endDate    = strval(Tools::getValue('end-date'));
            $status     = Tools::getValue('status');
            
            if (
                !$startDate ||
                empty($startDate) || 
                !$endDate ||
                empty($endDate)
            ) {
                $this->errors[] = $this->l('Fill all inputs');

            } else {
                $order          = new Order;
                $ordersByDate   = $order->getOrdersIdByDate($startDate, $endDate);

                $fitsOrders = [
                    [
                        'numer zamówienia', 
                        'data zamówienia', 
                        'status',
                        'kwota zamówienia', 
                        'kwota dostawy',
                        'ilość produktów',
                        'miasto'
                    ]
                ];

                foreach($ordersByDate as $orderByDate) {
                    $ordersDetail = new Order($orderByDate);

                    if ($ordersDetail->getCurrentState() == $status) 
                    {   
                        $orderState     = new OrderStateCore($status);
                        $customer       = new AddressCore($ordersDetail->id_customer);
                        $cartProducts   = $ordersDetail->getCartProducts();
                        $quantity       = 0;

                        foreach($cartProducts as $cartProduct) 
                        {   
                            $quantity += $cartProduct['product_quantity'];
                        }
 
                        $fitsOrders[] = [
                            $ordersDetail->reference,
                            $ordersDetail->date_add,
                            $orderState->name[1],
                            number_format($ordersDetail->total_paid, 2),
                            number_format($ordersDetail->total_shipping, 2),
                            $quantity,
                            $customer->city
                        ];
                    }
                }

                $this->generateCSV($fitsOrders);
            }
        }
    }

    /**
     * Get all order states
     *
     * @return array
     */
    private function getAllOrderStates(): array
    {
        $orderState         = new OrderStateCore();
        $allOrderStates     = $orderState->getOrderStates($this->idLang);

        return $allOrderStates;
    }

    /**
     * Generate options to select for form from orders state
     *
     * @param array $allOrderStates
     * @return array
     */
    private function generateSelectOrderState(array $allOrderStates): array
    {   
        $optionsOrderStates = [];

        foreach($allOrderStates as $os) {
            $optionsOrderStates[] = [
                'id'    => $os['id_order_state'],
                'name'  => $os['name']
            ];
        }

        return $optionsOrderStates;
    }

    /**
     * Generate CSV file
     *
     * @param array $orders
     * @return void
     */
    private function generateCSV(array $orders): void 
    {
        if (!file_exists(_PS_MODULE_DIR_.'raports/tmp/')) {
            mkdir(_PS_MODULE_DIR_.'raports/tmp/', 0777, true);
        }
        
        $new_csv = fopen(_PS_MODULE_DIR_.'raports/tmp/report.csv', 'w');
        foreach($orders as $order) {
            fputcsv($new_csv, $order);
        }
        
        fclose($new_csv);

        header("Content-type: text/csv");
        header("Content-disposition: attachment; filename = report.csv");

        readfile(_PS_MODULE_DIR_."raports/tmp/report.csv");

        exit();
    }
}
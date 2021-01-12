<?php

// require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';

class xmsOrderStatusUpdateProcessor extends modObjectProcessor
{
    public $objectType = 'msOrder';
    public $classKey = 'msOrder';
    public $languageTopics = ['minishop2:default'];
    public $permission = 'msorder_save';
    /** @var  miniShop2 $ms2 */
    protected $ms2;

    /**
     * @return bool
     */
    public function initialize()
    {
        $this->ms2 = $this->modx->getService('miniShop2');
        
        return parent::initialize();
    }

    /**
     * @return array|mixed|string
     */
    public function process()
    {
        $order_id = (int)$this->getProperty('order');
        if (empty($order_id)) {
            return $this->failure($this->modx->lexicon('ms2_err_ns'));
        }
        $status_id = (int)$this->getProperty('status');
        if (empty($status_id)) {
            return $this->failure($this->modx->lexicon('ms2_err_ns'));
        }

        /** @var msOrder $order */
        $order = $this->modx->getObject($this->classKey, $order_id);
        if (empty($order)) {
            return $this->failure($this->modx->lexicon('ms2_err_nf'));
        }

        if ((int)$order->get('status') !== $status_id) {
            $change_status = $this->ms2->changeOrderStatus($order->get('id'), $status_id);
            if ($change_status !== true) {
                return $this->failure($change_status);
            }
            $order = $this->modx->getObject($this->classKey, $order->get('id'), false);
        }

        return $this->success('', $order->toArray());
    }
}

return 'xmsOrderStatusUpdateProcessor';
<?php

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
            // Меняем статус нативными средствами miniShop2, чтобы запустились все связанные с этим события
            $change_status = $this->ms2->changeOrderStatus($order->get('id'), $status_id);
            if ($change_status !== true) {
                // Если при изменении статуса произошла ошибка, то отображаем менеджеру эту ошибку
                return $this->failure($change_status);
            }
            // Запрашиваем объект заказа снова из базы, чтобы получить его уже со всеми внесёнными изменениями
            $order = $this->modx->getObject($this->classKey, $order->get('id'), false);
        }

        return $this->success('', $order->toArray());
    }
}

return 'xmsOrderStatusUpdateProcessor';
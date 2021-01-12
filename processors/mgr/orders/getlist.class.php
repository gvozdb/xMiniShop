<?php

require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';
require_once MODX_CORE_PATH . 'components/minishop2/processors/mgr/orders/getlist.class.php';

class xmsOrderGetListProcessor extends msOrderGetListProcessor
{
    /** @var array $statuses */
    protected $statuses = [];
    
    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c = parent::prepareQueryBeforeCount($c);

        // Добавляем в выборку поля, которые нам потребуются для фильтрации списка статусов у конкретного заказа
        $c->select([
            'msOrder.status as status_id',
            'Status.rank as status_rank',
            'Status.fixed as status_fixed',
            'Status.final as status_final',
        ]);

        return $c;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function prepareArray(array $data)
    {
        // Запускаем родительский метод prepareArray, чтобы miniShop2 мог выполнить всё, что ему нужно
        $data = parent::prepareArray($data);

        // Запрашиваем весь список статусов разово, а потом используем его уже из кеша
        if (empty($this->statuses)) {
            $q = $this->modx->newQuery('msOrderStatus')
                ->select(['id', 'name', 'color', 'rank'])
                ->where(['active' => 1])
                ->sortby('rank', 'ASC');
            if ($q->prepare()->execute()) {
                $this->statuses = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Фильтруем список статусов для конкретного заказа
        $statuses = [];
        if (!empty($this->statuses)) {
            $statuses = array_filter($this->statuses, function ($status) use ($data) {
                if ($data['status_final']) {
                    // Если у заказа указан финальный статус,
                    // то выпадашку "Статус" вообще не отображаем
                    return false;
                }
                elseif ($data['status_fixed']) {
                    // Если у заказа указан фиксированный статус,
                    // то отображаем только те, что следуют после него
                    return $data['status_rank'] < $status['rank'];
                }

                // Если у заказа указан обычный статус (не фикс, не финал)
                // то отображаем все статусы, которые могут указываться у заказа
                return true;
            });
        }
        
        // Перестраиваем список меню
        $actions = [];
        foreach ($data['actions'] as $v) {
            // Перед пунктом меню "Удалить заказ" добавляем свой пункт "Статусы"
            if ($v['action'] === 'removeOrder') {
                $actions[] = [
                    'cls' => '',
                    'icon' => 'icon icon-flag-o',
                    'title' => $this->modx->lexicon('ms2_status'),
                    'action' => 'statusOrder',
                    'menu' => empty($statuses) ? false : array_map(function ($status) {
                        return [
                            'status' => $status,
                            'cls' => '',
                            'title' => '<span style="color:#' . $status['color'] . ';">' . $status['name'] . '</span>',
                            'action' => 'statusOrder',
                            'menu' => true,
                        ];
                    }, $statuses),
                ];
            }
            $actions[] = $v;
        }
        $data['actions'] = $actions;

        return $data;
    }
}

return 'xmsOrderGetListProcessor';
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
        $data = parent::prepareArray($data);
        
        if (empty($this->statuses)) {
            $q = $this->modx->newQuery('msOrderStatus')
                ->select(['id', 'name', 'color', 'rank'])
                ->where(['active' => 1])
                ->sortby('rank', 'ASC');
            if ($q->prepare()->execute()) {
                $this->statuses = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        $statuses = [];
        if (!empty($this->statuses)) {
            $statuses = array_filter($this->statuses, function ($status) use ($data) {
                if ($data['status_final']) {
                    return false; // (int)$status['id'] === (int)$data['status_id'];
                }
                elseif ($data['status_fixed']) {
                    return $data['status_rank'] < $status['rank'];
                }
                return true;
            });
        }
        
        //
        $actions = [];
        foreach ($data['actions'] as $v) {
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
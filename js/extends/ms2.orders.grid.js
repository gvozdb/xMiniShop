xMiniShop.grid.Orders = function (config) {
    Ext.applyIf(config, {
        url: xMiniShop.config['connector_url'],
        baseParams: {
            action: 'mgr/orders/getlist',
            sort: 'id',
            dir: 'desc',
        },
    });
    xMiniShop.grid.Orders.superclass.constructor.call(this, config);
};
Ext.extend(xMiniShop.grid.Orders, Ext.ComponentMgr.types['minishop2-grid-orders'], {
    /**
     * Меняет статус заказа "на лету"
     */
    statusOrder: function (btn, e, row) {
        if (typeof(row) !== 'undefined') {
            this.menu.record = row.data;
        }
        var id = this.menu.record.id; // id заказа из записи выбранного пункта меню
        var status = btn.initialConfig.baseConfig.status || undefined; // Объект статуса, который проброшен из расширенного PHP процессора mgr/orders/getlist, при помощи расширенного miniShop2.utils.getMenu
        if (!status || !status.id) {
            return;
        }
        
        this.loadMask.show(); // Показываем прелоадер на гриде, чтобы менеджер не понатыкал ещё куда-то, пока меняется статус заказа

        // Отсылаем запрос в наш процессор mgr/orders/status, в котором происходит смена статуса
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/orders/status',
                order: id,
                status: status.id,
            },
            listeners: {
                success: {
                    fn: function (r) {
                        // console.log('xMiniShop.grid.Orders / success / r', r);
                        
                        this.refresh(); // Обновляем гриду, чтобы сбросить прелоадер и обновить все статусы заказов в таблице
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        // console.log('xMiniShop.grid.Orders / failure / r', r);
                    },
                    scope: this
                },
            }
        });
    },
    
    /**
     * Перезаписываем метод, чтобы создать наши сабменюшки
     */
    addContextMenuItem: function (items) {
        var l = items.length;
        for (var i = 0; i < l; i++) {
            var options = items[i];

            if (options == '-') {
                this.menu.add('-');
                continue;
            }
            var h = Ext.emptyFn;
            if (options.handler) {
                h = eval(options.handler);
                if (h && typeof(h) == 'object' && h.xtype) {
                    h = this.loadWindow.createDelegate(this,[h],true);
                }
            } else {
                h = function(itm) {
                    var o = itm.options;
                    var id = this.menu.record.id;
                    if (o.confirm) {
                        Ext.Msg.confirm('',o.confirm,function(e) {
                            if (e == 'yes') {
                                var act = Ext.urlEncode(o.params || {action: o.action});
                                location.href = '?id='+id+'&'+act;
                            }
                        },this);
                    } else {
                        var act = Ext.urlEncode(o.params || {action: o.action});
                        location.href = '?id='+id+'&'+act;
                    }
                };
            }
            this.menu.add({
                id: options.id || Ext.id(),
                text: options.text,
                scope: options.scope || this,
                options: options,
                handler: h,
                menu: options.menu || undefined, // по-сути одна единственная строчка, ради которой мы расширили весь метод. Хм, и почему её не было сразу?..
            });
        }
    },
});
Ext.reg('minishop2-grid-orders', xMiniShop.grid.Orders);
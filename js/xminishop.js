var xMiniShop = function (config) {
    config = config || {};
    xMiniShop.superclass.constructor.call(this, config);
};
Ext.extend(xMiniShop, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}, ux: {}, fields: {}
});
Ext.reg('xminishop', xMiniShop);
xMiniShop = new xMiniShop();
Ext.define('Rd.view.components.cmbPpskGroups', {
    extend          : 'Ext.form.ComboBox',
    alias           : 'widget.cmbPpskGroups',
    fieldLabel      : 'PPSK Group',
    labelSeparator  : '',
    forceSelection  : true,
    queryMode       : 'local',
    valueField      : 'id',
    displayField    : 'name',
    typeAhead       : true,
    allowBlank      : false,
    name            : 'private_psk_id',
    mode            : 'remote',
    pageSize        : 0, // The value of the number is ignore -- it is essentially coerced to a boolean, and if true, the paging toolbar is displayed.
    include_all_option : true, 
    initComponent   : function() {
        var me= this;
        var s = Ext.create('Ext.data.Store', {
        fields: ['id', 'name'],
        listeners: {
            load: function(store, records, successful) {
            	if(me.include_all_option){
					me.setValue(0); //reset the value of the combobox when reloading to all schedules
					console.log("Set it to zero");
				}    
            },
            scope: me
        },
        proxy: {
                type    : 'ajax',
                format  : 'json',
                batchActions: true, 
                url     : '/cake4/rd_cake/private-psks/index-combo.json',
                reader: {
                    type            : 'json',
                    rootProperty    : 'items',
                    messageProperty : 'message',
                    totalProperty   : 'totalCount' //Required for dynamic paging
                }                              
            },
            autoLoad    : true
        });
        
        if(me.include_all_option){
        	s.getProxy().setExtraParams({include_all_option: me.include_all_option });
        }             
        me.store = s;
        this.callParent(arguments);
    }
});

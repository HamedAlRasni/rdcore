Ext.define('Rd.view.permananetUsers.winUserEmailDetail', {
    extend      : 'Ext.window.Window',
    alias       : 'widget.winUserEmailDetail',
    closable    : true,
    draggable   : true,
    resizable   : true,
    title       : 'e-mail user credentials',
    width       : 400,
    height      : 350,
    plain       : true,
    border      : false,
    layout      : 'fit',
    glyph       : Rd.config.icnEmail,
    autoShow    : false,
    userId      : undefined, //One of these will be used by the target
    defaults: {
            border: false
    },
    requires: [
        'Ext.form.field.Text'
    ],
     initComponent: function() {
        var me = this;  
        var frmData = Ext.create('Ext.form.Panel',{
            border:     false,
            layout:     'anchor',
            defaults: {
                anchor: '100%'
            },
            itemId:     'scrnData',
            autoScroll: true,
            fieldDefaults: {
                msgTarget       : 'under',
                labelClsExtra   : 'lblRd',
                labelAlign      : 'left',
                labelSeparator  : '',
                labelClsExtra   : 'lblRd',
                labelWidth      : Rd.config.labelWidth,
                maxWidth        : Rd.config.maxWidth, 
                margin          : Rd.config.fieldMargin
            },
            defaultType: 'textfield',
            buttons: [{xtype: 'btnCommon', itemId  : 'send'}],
            items: [
                    {
                        xtype       : 'textfield',
                        name        : "id",
                        hidden      : true,
                        value       : me.userId
                    },
                    {
                        xtype       : 'displayfield',
                        fieldLabel  : 'Username',
                        name        : 'username',
                        value       : me.username
                    },
                    {
                        xtype       : 'textfield',
                        fieldLabel  : 'e-mail',
                        name        : "email",
                        allowBlank  : false,
                        blankText   : i18n('sSupply_a_value'),
                        labelClsExtra: 'lblRdReq',
                        vtype       : 'email',
                        value       : me.email
                    },
                    {
                        xtype       : 'textareafield',
                        grow        : true,
                        name        : 'message',
                        fieldLabel  : 'Extra message'
                    }
            ]
        });

        me.items = frmData;
        me.callParent(arguments);
    }
});

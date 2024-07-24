Ext.define('Rd.view.aps.gridAccessPointExits' ,{
    extend      :'Ext.grid.Panel',
    alias       : 'widget.gridAccessPointExits',
    multiSelect : true,
    stateful    : true,
    stateId     : 'StateGridAccessPointExitsId',
    stateEvents :['groupclick','columnhide'],
    border      : false,
    requires    : [
        'Rd.view.components.ajaxToolbar'
    ],
    viewConfig  : {
        loadMask:true
    },
    urlMenu     : '/cake4/rd_cake/ap-profiles/menu_for_exits_grid.json',
    initComponent: function(){
        var me      = this;
        me.store    = Ext.create('Rd.store.sAccessPointExits',{});
        me.store.getProxy().setExtraParam('ap_profile_id',me.apProfileId);
        me.store.load();

        me.tbar     = Ext.create('Rd.view.components.ajaxToolbar',{'url': me.urlMenu});
        
        me.columns  = [
         //   {xtype: 'rownumberer',stateId: 'StateGridAccessPointExitsId1'},
            { 
                text    : i18n('sType'),                 
                dataIndex: 'type',          
                tdCls   : 'gridTree', 
                flex    : 1,
                xtype   : 'templatecolumn', 
                tpl     : new Ext.XTemplate(
                    '<tpl if="type==\'bridge\'"><div class="fieldGreyWhite"><i class="fa fa-bars"></i> '+' '+'Bridge'+'</div></tpl>',
                    '<tpl if="type==\'captive_portal\'"><div class="fieldPurpleWhite"><i class="fa fa-key"></i> '+' '+'Captive Portal'+'</div></tpl>',
                    '<tpl if="type==\'nat\'"><div class="fieldGreenWhite"><i class="fa fa-arrows-alt"></i> '+' '+'NAT+DHCP'+'</div></tpl>',
                    '<tpl if="type==\'tagged_bridge\'"><div class="fieldBlueWhite"><i class="fa fa-tag"></i> '+' '+'Layer 2 Tagged Ethernet Bridge (&#8470; {vlan})'+'</div></tpl>',
                    '<tpl if="type==\'openvpn_bridge\'"><div class="fieldBlueWhite"><i class="fa fa-quote-right"></i> '+' '+'OpenVPN Bridge'+'</div></tpl>',
                    '<tpl if="type==\'tagged_bridge_l3\'"><div class="fieldBlue"><i class="fa fa-tag"></i> '+' '+'Layer 3 Tagged Ethernet Bridge (&#8470; {vlan})'+'</div></tpl>',
                    '<tpl if="type==\'pppoe_server\'"><div class="fieldBlueWhite"><i class="fa fa-link"></i> '+' '+'PPPoE Server (Accel)'+'</div></tpl>'
                ),
            
                stateId: 'StateGridAccessPointExitsId2'
            },
            { 
                text    :   i18n("sConnects_with"),
                sortable: false,
                flex    : 1, 
                tdCls   : 'gridTree', 
                xtype   :  'templatecolumn', 
                tpl:    new Ext.XTemplate(                    
                    '<tpl if="type == \'nat\' && vlan &gt; 0">',
                        '<div class="fieldBlueWhite">',
                            '<i class="fa fa-sitemap"></i> LAN Side VLAN: &#8470; {vlan}',
                        '</div>',
                    '<tpl else>',
                        '<tpl if="(Ext.isEmpty(connects_with)&&(type!=\'tagged_bridge_l3\'))">',
                            '<div class=\"fieldRedWhite\">',
                                '<i class="fa fa-exclamation-circle"></i> '+i18n('sNo_one'),
                            '</div>',
                        '</tpl>',
                    '</tpl>',
                    '<tpl for="connects_with">',     // interrogate the realms property within the data
                        "<tpl><div class=\"fieldGreyWhite\">{name}</div></tpl>",
                    '</tpl>'
                ),
                dataIndex: 'connects_with',stateId: 'StateGridAccessPointExitsId3'
            },
            { 
                text    : 'Firewall Profile',
                sortable: false,
                flex    : 1, 
                tdCls   : 'gridTree', 
                xtype   :  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                    '<tpl if="apply_firewall_profile">',
                    	"<tpl><div class=\"fieldBlueWhite\"><span style=\"font-family:FontAwesome;\">&#xf06d;</span>  {firewall_profile_name}</div></tpl>",
                    '<tpl else>',
                        "<tpl><div class=\"fieldGreyWhite\">No Active Firewall</div></tpl>",
                    '</tpl>'
                ),
                dataIndex: 'apply_firewall_profile',stateId: 'StateGridAccessPointExitsId4'
            },
            { 
                text    : 'SQM Profile',
                sortable: false,
                flex    : 1, 
                tdCls   : 'gridTree', 
                xtype   :  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                    '<tpl if="apply_sqm_profile">',
                    	"<tpl><div class=\"fieldBlueWhite\"><i class=\"fa fa-th\"></i>  {sqm_profile_name}</div></tpl>",
                    '<tpl else>',
                        "<tpl><div class=\"fieldGreyWhite\">SQM Not Enabled</div></tpl>",
                    '</tpl>'
                ),
                dataIndex: 'apply_firewall_profile',stateId: 'StateGridAccessPointExitsId5'
            }
        ];
        me.callParent(arguments);
    }
});

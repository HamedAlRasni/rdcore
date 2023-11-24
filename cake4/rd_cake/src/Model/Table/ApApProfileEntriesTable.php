<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class ApApProfileEntriesTable extends Table {

    public function initialize(array $config):void{  
        $this->addBehavior('Timestamp');
        $this->belongsTo('Aps', [
                'className' => 'Aps',
                'foreignKey' => 'ap_id'
            ]);
        $this->hasMany('ApApProfileEntries',  ['dependent' => true]);
        $this->hasMany('ApStaticEntryOverrides',  ['dependent' => true]);	
    }
}

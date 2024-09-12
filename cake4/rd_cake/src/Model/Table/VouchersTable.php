<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class VouchersTable extends Table
{
    public function initialize(array $config):void{
        $this->addBehavior('Timestamp');
        $this->addBehavior('FreeRadius',
            [
                'for_model' => 'Vouchers'
            ]
        );
        $this->belongsTo('Clouds');      
        $this->belongsTo('Profiles',['propertyName'  => 'real_profile']);
        $this->belongsTo('Realms',['propertyName'  => 'real_realm']);
        $this->hasMany('Radchecks',[
            'dependent' => true,
            'cascadeCallbacks' =>true,
            'foreignKey' => 'username',
            'bindingKey' => 'name'
        ]);
        $this->hasMany('Radreplies',[
            'dependent' => true,
            'cascadeCallbacks' =>true,
            'foreignKey' => 'username',
            'bindingKey' => 'name'
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator{
        $validator = new Validator();
        $validator
            ->notEmpty('name', 'A name is required')
            ->add('name', [ 
                'nameUnique' => [
                    'message'   => 'The name you provided is already taken. Please provide another one.',
                    'rule'    => ['validateUnique', ['scope' => 'cloud_id']],
                    'provider'  => 'table'
                ]
            ]);
        return $validator;
    }
       
}

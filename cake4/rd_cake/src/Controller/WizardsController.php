<?php

namespace App\Controller;
use App\Controller\AppController;

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

use Cake\Utility\Inflector;

class WizardsController extends AppController{

    protected $ctc              = "_Click-To-Connect";
    protected $reg              = "_User-Registration";
    protected $prof_comp_prefix = 'SimpleAdd_';
  
    public function initialize():void{  
        parent::initialize(); 
        
        $this->loadComponent('Aa');
        
        $this->loadModel('Clouds');
        $this->loadModel('PermanentUsers');
        $this->loadModel('Realms');       
        $this->loadModel('DynamicDetails');
        $this->loadModel('DynamicDetailCtcs');
        $this->loadModel('DynamicPhotos');
        
         $this->loadModel('DynamicPairs');
        $this->loadModel('Radchecks');
        
        //Nov2020 Clouds;Sites;Networks
        $this->loadModel('Clouds');
        $this->loadModel('Sites');
        $this->loadModel('Networks');
        
        //Sept2019 -Add Simple Profiles
        $this->loadModel('Profiles');
        $this->loadModel('ProfileComponents');
        
        $this->loadModel('Radusergroups');    
        $this->loadModel('Radgroupchecks');
        $this->loadModel('Radgroupreplies');
        
        
        $this->loadModel('DynamicClients');
        $this->loadModel('DynamicClientRealms'); 
        
        $this->loadModel('ApProfiles');
        $this->loadModel('ApProfileEntries');
        $this->loadModel('ApProfileExits');
        $this->loadModel('ApProfileExitApProfileEntries');
        $this->loadModel('ApProfileExitCaptivePortals');
        $this->loadModel('ApProfileSettings');
        
        $this->loadModel('Meshes');
        $this->loadModel('MeshEntries');
        $this->loadModel('MeshExits');
        $this->loadModel('MeshExitMeshEntries');
        $this->loadModel('MeshExitCaptivePortals');
        $this->loadModel('NodeSettings');
        $this->loadModel('Timezones');    
    }
    
    public function cancel(){
    
    
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        
        $user_id    = $user['id'];
        $req_d		= $this->request->getData();
      
        $this->new_name = $req_d['name'];
        
        if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove laeding spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }   
        
        $this->cloud_id  = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        
        //Now we have our cloud id, now we can delete
        $this->Realms->deleteAll(['Realms.name' => $this->new_name,'Realms.cloud_id'=> $this->cloud_id]);
        $this->DynamicDetails->deleteAll(['DynamicDetails.name' => $this->new_name,'DynamicDetails.cloud_id'=> $this->cloud_id]);
        
        $this->DynamicClients->deleteAll(['DynamicClients.name' => $this->new_name,'DynamicClients.cloud_id'=> $this->cloud_id]);
        
        //Delete the profiles and their linked profile components
        $e_pc_basic = $this->Profiles->find()->where(['Profiles.name' => $this->new_name,'Profiles.cloud_id'=>$this->cloud_id])->first();
        if($e_pc_basic){
            $pc_name = $this->prof_comp_prefix.$e_pc_basic->id;
            $this->ProfileComponents->deleteAll(['ProfileComponents.name' => $pc_name,'ProfileComponents.cloud_id'=>$this->cloud_id]);
            $this->Profiles->deleteAll(['Profiles.name' => $this->new_name,'Profiles.cloud_id'=>$this->cloud_id]);
        }
        
        $e_pc_c_t_c = $this->Profiles->find()->where(['Profiles.name' => $this->new_name.$this->ctc,'Profiles.cloud_id'=>$this->cloud_id])->first();
        if($e_pc_c_t_c){
            $pc_name = $this->prof_comp_prefix.$e_pc_c_t_c->id;
            $this->ProfileComponents->deleteAll(['ProfileComponents.name' => $pc_name,'ProfileComponents.cloud_id'=>$this->cloud_id]);
            $this->Profiles->deleteAll(['Profiles.name' => $this->new_name.$this->ctc,'Profiles.cloud_id'=>$this->cloud_id]);
            $this->{'Radusergroups'}->deleteAll(['Radusergroups.username' => $this->new_name.$this->ctc, 'Radusergroups.groupname' => $pc_name]);
            $this->{'Radgroupreplies'}->deleteAll(['groupname' => $pc_name]);
            $this->{'Radgroupchecks'}->deleteAll(['groupname' => $pc_name]);
        }
        
        $e_pc_reg = $this->Profiles->find()->where(['Profiles.name' => $this->new_name.$this->reg,'Profiles.cloud_id'=>$this->cloud_id])->first();
        if($e_pc_reg){
            $pc_name = $this->prof_comp_prefix.$e_pc_reg->id;
            $this->ProfileComponents->deleteAll(['ProfileComponents.name' => $pc_name,'ProfileComponents.cloud_id'=>$this->cloud_id]);
            $this->Profiles->deleteAll(['Profiles.name' => $this->new_name.$this->reg,'Profiles.cloud_id'=>$this->cloud_id]);
            $this->{'Radusergroups'}->deleteAll(['Radusergroups.username' => $this->new_name.$this->reg, 'Radusergroups.groupname' => $pc_name]);
            $this->{'Radgroupreplies'}->deleteAll(['groupname' => $pc_name]);
            $this->{'Radgroupchecks'}->deleteAll(['groupname' => $pc_name]);
        }
        
        
        $this->ApProfiles->deleteAll(['ApProfiles.name' => $this->new_name,'ApProfiles.cloud_id'=>$this->cloud_id]);
        $this->Meshes->deleteAll(['Meshes.name' => $this->new_name,'Meshes.cloud_id'=>$this->cloud_id]);
  
        //Access Provder 
        $user_name = preg_replace('/\s+/', '_', $this->new_name);
        $user_name = strtolower($user_name);
        
        //Delete the sample file
        $dest  = WWW_ROOT."img/dynamic_photos/".$user_name.".jpg";
        unlink($dest);
        
        $this->DynamicClients->deleteAll(
            ['DynamicClients.nasidentifier LIKE ' => 'mcp_%','DynamicClients.cloud_id'=>$this->cloud_id]);
        
        $this->PermanentUsers->deleteAll(
            ['PermanentUsers.username' => $user_name.'@'.$user_name,'PermanentUsers.cloud_id'=>$this->cloud_id]);
        
        $this->Radchecks->deleteAll(['Radchecks.username' => $user_name.'@'.$user_name]);
        
        $this->PermanentUsers->deleteAll(['PermanentUsers.username' => 'click_to_connect@'.$user_name,'PermanentUsers.cloud_id'=>$this->cloud_id]);
        
        $this->Radchecks->deleteAll(['Radchecks.username' => 'click_to_connect@'.$user_name]);
               
        $this->set([
        	'items'     => [],
            'success'   => true
       	]);
		$this->viewBuilder()->setOption('serialize', true);
    }
    
    public function newSiteStepOne(){
	
	    $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        
        $user_id        = $user['id'];
        $this->user_id  = $user_id;
        $req_d		    = $this->request->getData();
        $this->new_name = $req_d['name'];
        
        if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove leading spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }
        
        $this->pwd      = $req_d['password'];
        
        $this->ssid_wireless    = $req_d['ssid_wireless'];
        $this->ssid_guest       = $req_d['ssid_guest'];
        $this->key_wireless     = $req_d['key_wireless'];
        
        $exist_test = $this->_test_if_exists($this->new_name);
        
        if(!$exist_test){
            $this->_add_items($this->new_name);
        }else{        
        	$this->set([
		    	'errors'    => ['name' => "Items with this name alreay exist: $exist_test"],
                'success'   => false,
                'message'   => [$exist_test." already exist"]
		   	]);
			$this->viewBuilder()->setOption('serialize', true);
            return;
        }
        
        $this->set([
	    	'items'     => [],
            'success'   => true,
	   	]);
		$this->viewBuilder()->setOption('serialize', true);
    }
    
    public function changeTheme(){
    
         $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        $user_id 	= $user['id'];
        $req_d		= $this->request->getData();
        $this->new_name = $req_d['name'];
        
        if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove laeding spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }
        
        $this->cloud_id  = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        
        $e = $this->DynamicDetails->find()
            ->where(
                [
                    'DynamicDetails.name'        => $this->new_name,
                    'DynamicDetails.cloud_id'    => $this->cloud_id,
                ]
            )
            ->first();
        
        if($e){
            $e->theme = $req_d['theme'];
            $this->DynamicDetails->save($e);  
        }
         
        $this->set([
	    	'data'     => [],
            'success'   => true,
	   	]);
		$this->viewBuilder()->setOption('serialize', true);
    }
    
    public function viewCountryAndTimezone(){

        $data           = $this->_getDefaultSettings(); 
        $d              = [];
        $d['country']   = $data['country'];
        $d['timezone']  = $data['timezone'];
        $this->set([
	    	'data'     => $d,
            'success'   => true,
	   	]);
		$this->viewBuilder()->setOption('serialize', true);
    }
    
    private function _getDefaultSettings(){
    
        Configure::load('MESHdesk'); 
        $data  = Configure::read('common_node_settings'); //Read the defaults

        $this->loadModel('UserSettings');   
        $q_r = $this->{'UserSettings'}->find()->where(['user_id' => -1])->all();
        if($q_r){
            foreach($q_r as $s){
                //ALL Captive Portal Related default settings will be 'cp_<whatever>'
                if(preg_match('/^cp_/',$s->name)){
                    $name           = preg_replace('/^cp_/', '', $s->name);
                    $data[$name]    = $s->value;     
                }
                if($s->name == 'password'){
                    $data[]         = $s->value;
                    $data['password_hash']  = $this->_make_linux_password($s->value);   
                }
                
                if($s->name == 'country'){
                    $data[$s->name]  = $s->value;
                } 
                if($s->name == 'heartbeat_dead_after'){
                    $data[$s->name]  = $s->value;
                } 
                if($s->name == 'timezone'){
                    $data[$s->name]  = $s->value;
                    $ent_tz = $this->{'Timezones'}->find()->where(['Timezones.id' => $s->value])->first();
                    if($ent_tz){
                        $data['tz_name'] = $ent_tz->name;
                        $data['tz_value']= $ent_tz->value;
                    }
                } 
            }
        }
        return $data;
    }
    
    public function newSiteStepTwo(){
       
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        
        $user_id    = $user['id'];
        $req_d		= $this->request->getData();
        $this->new_name = $req_d['name'];
        
         if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove laeding spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }      
        
        $this->cloud_id  = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        
        $q_r = $this->DynamicDetails->find()
            ->where([
                'DynamicDetails.name'        => $this->new_name,
                'DynamicDetails.cloud_id'    => $this->cloud_id,
            ])
            ->first();
        if($q_r){
            $id = $q_r->id;
            $d = [];
            $d['id'] = $id;
            $check_items = ['voucher_login_check','user_login_check','eth_br_for_all'];
          
            foreach($check_items as $i){
                if(isset($req_d[$i])){
                    $d[$i] = 1;
                }else{
                    $d[$i] = 0;
                }
            }
            $this->DynamicDetails->patchEntity($q_r, $d);
            $this->DynamicDetails->save($q_r); 
            
            //Update the DynamcDetailCtcs entry
            $d_ctcs['connect_check'] = 0;
            if(isset($req_d['connect_check'])){
                $d_ctcs['connect_check'] = 1;
            }
            $q_ctc = $this->DynamicDetailCtcs->find()
                ->where([
                    'DynamicDetailCtcs.dynamic_detail_id'   => $id
                ])
                ->first();
            if($q_ctc){
                $this->DynamicDetailCtcs->patchEntity($q_ctc, $d_ctcs);
                $this->DynamicDetailCtcs->save($q_ctc);
            }
             
        }
        
        //Try to find the timezone and its value
        $tz_id  = $this->request->getData('timezone');
        $ent_tz  = $this->{'Timezones'}->find()->where(['Timezones.id' => $tz_id])->first();
        if($ent_tz){
            $req_d['tz_name']     = $ent_tz->name;
            $req_d['tz_value']    = $ent_tz->value;
        }
        
        //$req_d['timezone'] is the value you have to use to set on the dynamic_clients
        $mesh_name_underscored = preg_replace('/\s+/', '_', $this->new_name);
        $mesh_name_underscored = strtolower($mesh_name_underscored);  
        $name_like = 'MESHdesk_'.$mesh_name_underscored.'_mcp_%';

        $e_dc = $this->{'DynamicClients'}->find()
            ->where(['DynamicClients.name LIKE' =>$name_like,'DynamicClients.cloud_id' => $this->cloud_id])
            ->first();
        if($e_dc){ 
   
            $tz_id = $req_d['timezone'];
            $this->{'DynamicClients'}->patchEntity($e_dc, ['timezone' => $tz_id,'session_auto_close' => 1]);
            $this->{'DynamicClients'}->save($e_dc);
        }
          
        //== Timezone and Country for Mesh
        $q_m = $this->Meshes->find()
            ->where([
                'Meshes.name'        => $this->new_name,
                'Meshes.cloud_id'    => $this->cloud_id,
            ])
            ->first();
        
        if($q_m){
            $mesh_id = $q_m->id;
            //Find the first
            $q_ns = $this->NodeSettings->find()
                ->where([
                    'NodeSettings.mesh_id'   => $mesh_id
                ])
                ->first();
            $d_ns               = array();
            if($q_ns){
                $ns_id          = $q_ns->id;
                $d_ns['id']     = $ns_id;
            }  
            
            $d_ns['mesh_id']    	= $mesh_id;
            $d_ns['tz_name']    	= $req_d['tz_name'];
            $d_ns['tz_value']   	= $req_d['tz_value'];
            $d_ns['country']    	= $req_d['country'];         
            $new_pwd            	= $this->_make_linux_password($req_d['password']);
            $d_ns['password']   	= $req_d['password'];
            $d_ns['password_hash'] 	= $new_pwd;
            
            if($q_ns){
                $this->NodeSettings->patchEntity($q_ns, $d_ns);  
            }else{
                $q_ns = $this->NodeSettings->newEntity($d_ns);
            }
            $this->NodeSettings->save($q_ns);
            
        }
        
        //== Timezone and Country for ApProfile
        $q_ap = $this->ApProfiles->find()
            ->where([
                    'ApProfiles.name'        => $this->new_name,
                    'ApProfiles.cloud_id'    => $this->cloud_id,
                ])
            ->first();
 
        if($q_ap){
            $ap_profile_id = $q_ap->id;
            //Find the first
            $q_ap_s = $this->ApProfileSettings->find()
                ->where([
                    'ApProfileSettings.ap_profile_id'   => $ap_profile_id
                ])
                ->first();
            
            $d_ap_s             = [];
            if($q_ap_s){
                $ap_s_id            = $q_ap_s->id;
                $d_ap_s['id']       = $ap_s_id;
            }    
            
            $d_ap_s['ap_profile_id']    = $ap_profile_id;
            $d_ap_s['tz_name']  		= $req_d['tz_name'];
            $d_ap_s['tz_value'] 		= $req_d['tz_value'];
            $d_ap_s['country']  		= $req_d['country'];            
            $new_pwd                    = $this->_make_linux_password($req_d['password']);
            $d_ap_s['password']         = $req_d['password'];
            $d_ap_s['password_hash']    = $new_pwd;
            
            if($q_ap_s){
                $this->ApProfileSettings->patchEntity($q_ap_s, $d_ap_s);  
            }else{
                $q_ap_s = $this->ApProfileSettings->newEntity($d_ap_s);
            }           
            $this->ApProfileSettings->save($q_ap_s);
        }
        
        $this->set([
        	'items'     => [],
            'success'   => true
       	]);
		$this->viewBuilder()->setOption('serialize', true);
    }
    
     public function viewLogo(){
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }   
        $user_id        = $user['id'];
        $req_q          = $this->request->getQuery();
        $this->new_name = $req_q['name'];
        $this->cloud_id = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        $q_r            = $this->DynamicDetails->find()
            ->where(['DynamicDetails.name' => $this->new_name,'DynamicDetails.cloud_id' => $this->cloud_id])
            ->first();
        if($q_r){
        
        	$this->set([
		    	'data'     => $q_r->toArray(),
                'success'   => true
		   	]);
			$this->viewBuilder()->setOption('serialize', true);
        
        }else{
        
        	$this->set([
                'success'   => false
		   	]);
			$this->viewBuilder()->setOption('serialize', true);
        }
    }
    
    public function uploadLogo($id = null){

        //__ Authentication + Authorization __
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }   

        $this->viewBuilder()->setLayout('ext_file_upload');
        
        $path_parts     = pathinfo($_FILES['photo']['name']);
        $unique         = time();
        $dest           = WWW_ROOT."img/dynamic_details/".$unique.'.'.$path_parts['extension'];
        $dest_realm     = WWW_ROOT."img/realms/".$unique.'.'.$path_parts['extension'];
        $dest_www       = "/cake4/rd_cake/webroot/img/dynamic_details/".$unique.'.'.$path_parts['extension'];

        //Now add....
        $data['photo_file_name']  = $unique.'.'.$path_parts['extension'];
        
        $user_id    = $user['id'];
        $req_d		= $this->request->getData();
        $this->new_name = $req_d['name'];
       
        if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove laeding spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }
        
        $this->cloud_id = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        $q_r            = $this->DynamicDetails->find()
            ->where(['DynamicDetails.name' => $this->new_name,'DynamicDetails.cloud_id' => $this->cloud_id])
            ->first();
              
        if($q_r){ 
            $old_file       = $q_r->icon_file_name; 
            $q_r->icon_file_name = $unique.'.'.$path_parts['extension']; 
            if($this->DynamicDetails->save($q_r)){
                move_uploaded_file ($_FILES['photo']['tmp_name'] , $dest);
                //Remove old files
                $file_to_delete     = WWW_ROOT."img/dynamic_details/".$old_file;
                $file_to_delete_r   = WWW_ROOT."img/realms/".$old_file;
                if(file_exists($file_to_delete)){
                    unlink($file_to_delete);
                }
                if(file_exists($file_to_delete_r)){
                    unlink($file_to_delete_r);
                }
                
                //----------------------------
                //Also take care of the realm 
                copy($dest,$dest_realm);
                $q_realm = $this->Realms->find()
                    ->where(['Realms.name' => $this->new_name,'Realms.cloud_id' => $this->cloud_id])
                    ->first();
                if($q_realm){
                    $q_realm->icon_file_name = $unique.'.'.$path_parts['extension'];
                    $this->Realms->save($q_realm);
                }
                //---------------------------- 
                
                $this->set(['json_return' => [
					'success' 			=> true,
					'id'      			=> $q_r->id,
				    'icon_file_name'	=> $unique.'.'.$path_parts['extension']
				]]);                       
                
            }else{
                $errors = $q_r->errors();
                $a 		= [];
                foreach(array_keys($errors) as $field){
                    $detail_string = '';
                    $error_detail =  $errors[$field];
                    foreach(array_keys($error_detail) as $error){
                        $detail_string = $detail_string." ".$error_detail[$error];   
                    }
                    $a[$field] = $detail_string;
                }
                
                $this->set(['json_return' => [
				    'errors' 	=> $a,
				    'message' 	=> __('Problem uploading Logo'),
				    'success'	=> false]
				]);  
            }  
        }else{
            $errors = $q_r->errors();
            $a 		= [];
            foreach(array_keys($errors) as $field){
                $detail_string = '';
                $error_detail =  $errors[$field];
                foreach(array_keys($error_detail) as $error){
                    $detail_string = $detail_string." ".$error_detail[$error];   
                }
                $a[$field] = $detail_string;
            }
            
            $this->set(['json_return' => [
			    'errors' 	=> $a,
			    'message' 	=> __('Problem uploading Logo'),
			    'success'	=> false]
			]);
        }
    }
    
     public function indexPhoto(){
    
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }   
        $user_id        = $user['id'];
        $req_q    		= $this->request->getQuery();
        $this->new_name = $req_q['name'];
        $this->cloud_id = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        
        $q_r            = $this->DynamicDetails->find()
            ->where(['DynamicDetails.name' => $this->new_name,'DynamicDetails.cloud_id' => $this->cloud_id])
            ->first();
        
        if($q_r){
        
            $id = $q_r->id;
            $items = [];
        
            $q_p = $this->DynamicPhotos->find()
                ->where(['DynamicPhotos.dynamic_detail_id' => $id])
                ->all();
                
            $fields = $this->{'DynamicPhotos'}->getSchema()->columns();
   
            foreach($q_p as $p){
            
           	    $row = array();
                foreach($fields as $field){
                    $row["$field"]= $p->{"$field"};
                }
                
                $photo = Configure::read('paths.real_photo_path').$p->file_name;
                $row['img']= "/cake4/rd_cake/webroot/files/image.php?width=400&height=200&image=".$photo;      
           	    array_push($items,$row);
            }
            
            $this->set([
                'items'     => $items,
                'success'   => true
		   	]);
			$this->viewBuilder()->setOption('serialize', true);
       
        }else{
            $this->set([
                'success'   => false
		   	]);
			$this->viewBuilder()->setOption('serialize', true);
        }
    }
    
    public function uploadPhoto(){

        //__ Authentication + Authorization __
        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        $user_id        = $user['id'];
         
        $this->viewBuilder()->setLayout('ext_file_upload');  
        
        //We find the ID for this name....
        $user_id        = $user['id'];
        $req_d		    = $this->request->getData();
        $this->new_name = $req_d['name'];
        
        if(isset($req_d['name'])){
            $this->new_name =  preg_replace('/^\s+/', '', $this->new_name); //Remove laeding spaces
            $this->new_name =  preg_replace('/\s+$/', '', $this->new_name); //Remove trailing spaces
        }
        
        
        $this->cloud_id = $this->_return_cloud_id_from_name($this->new_name,$user_id);
        $q_r            = $this->DynamicDetails->find()
            ->where(['DynamicDetails.name' => $this->new_name,'DynamicDetails.cloud_id' => $this->cloud_id])
            ->first();
       
        $check_items = array('active','include_title','include_description');
        foreach($check_items as $ci){
            if(isset($req_d[$ci])){
                $req_d[$ci] = 1;
            }else{
                $req_d[$ci] = 0;
            }
        }
        
        $path_parts     = pathinfo($_FILES['photo']['name']);
        $unique         = time();
        $dest           =  WWW_ROOT."img/dynamic_photos/".$unique.'.'.$path_parts['extension'];
        $dest_www       = "/cake4/rd_cake/webroot/img/dynamic_photos/".$unique.'.'.$path_parts['extension'];

        $req_d['dynamic_detail_id']   = $q_r->id;
        $req_d['file_name']           = $unique.'.'.$path_parts['extension'];
        
        $entity = $this->DynamicPhotos->newEntity($req_d); 
        if($this->DynamicPhotos->save($entity)){
            move_uploaded_file ($_FILES['photo']['tmp_name'] , $dest);
            
            $this->set(['json_return' => [
		        	'success' => true,
		        	'id'      => $entity->id
		        ]
			]); 

        }else{
            $message = 'Error';
            $errors = $entity->errors();
            $a = [];
            foreach(array_keys($errors) as $field){
                $detail_string = '';
                $error_detail =  $errors[$field];
                foreach(array_keys($error_detail) as $error){
                    $detail_string = $detail_string." ".$error_detail[$error];   
                }
                $a[$field] = $detail_string;
            }
            
            $this->set(['json_return' => [
				    'errors' 	=> $a,
				    'message' 	=> array("message"   => __('Problem uploading photo')),
				    'success'	=> false
		        ]
			]); 
        }
    }
    
    public function deletePhoto() {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}

        $user = $this->Aa->user_for_token($this);
        if(!$user){   //If not a valid user
            return;
        }
        $req_d		= $this->request->getData();   

	    if(isset($req_d['id'])){   //Single item delete
            //Get the filename to delete
            $entity = $this->DynamicPhotos->get($req_d['id']);
            if($entity){
                $file_to_delete = WWW_ROOT."img/dynamic_photos/".$entity->file_name;
                if($this->DynamicPhotos->delete($entity)){
                    if(file_exists($file_to_delete)){
                        unlink($file_to_delete);
                    }
                }
            }     
            
        }else{                          //Assume multiple item delete
            foreach($req_d as $d){
                //Get the filename to delete
                $entity = $this->DynamicPhotos->get($d['id']);
                if($entity){
                    $file_to_delete = WWW_ROOT."img/dynamic_photos/".$entity->file_name;
                    if($this->DynamicPhotos->delete($entity)){
                        if(file_exists($file_to_delete)){
                            unlink($file_to_delete);
                        }
                    }
                }
            }
        }
        
        $this->set(['success' => true]);
		$this->viewBuilder()->setOption('serialize', true);
	}
    
    private function _add_items($name){
    
         //Access Provder 
        $ap_name        = preg_replace('/\s+/', '_', $this->new_name);
        $ap_name        = strtolower($ap_name);
        $this->ap_name  = $ap_name; //Make it global accessable
              
        //We start with a Cloud        
        $user_cloud_count = $this->{'Clouds'}->find()->where(['Clouds.user_id' => $this->user_id])->count();
        
      
        //Add A Cloud / Site and Network
        $cloud_name     = $name;
        $site_name      = "Site ".$name;
        $network_name   = "Network ".$name;     
        $e_cloud = $this->{'Clouds'}->newEntity(['name' => $cloud_name,'user_id' => $this->user_id]);
        $this->{'Clouds'}->save($e_cloud);
        $this->cloud_id = $e_cloud->id;
        $e_site = $this->{'Sites'}->newEntity(['name' => $site_name,'cloud_id' => $e_cloud->id]);
        $this->{'Sites'}->save($e_site);
        $e_network = $this->{'Networks'}->newEntity(['name' => $network_name,'site_id' => $e_site->id]);
        $this->{'Networks'}->save($e_network);
            
           
        //return; 
        $d_common = [];
        $d_common['cloud_id']               = $this->cloud_id;
        $d_common['name']                   = $name;
        $d_common['tree_tag_id']            = $e_network->id;
        
        $this->d_common = $d_common; 
        
        $d_common['suffix']                 = $this->ap_name; 
        $d_common['suffix_permanent_users'] = true;
        $d_common['suffix_vouchers']        = false;        //ONLY for the permanent users      
    
        $e_realm = $this->{'Realms'}->newEntity($d_common);
        $this->{'Realms'}->save($e_realm);
        $realm_id =  $e_realm->id;
      
      	//==== Default settings if this is the user's first 
     /* 	if($user_cloud_count == 0){
      		$this->loadModel('UserSettings');
      		
      		$e_cloud = $this->{'UserSettings'}->newEmptyEntity();
          	$e_cloud->user_id    = $this->user_id;
            $e_cloud->name       = 'cloud_id';
            $e_cloud->value      = $this->cloud_id;
          	$this->UserSettings->save($e_cloud);
          	
          	$e_cv = $this->{'UserSettings'}->newEmptyEntity();
          	$e_cv->user_id    = $this->user_id;
            $e_cv->name       = 'compact_view';
            $e_cv->value      = 1;
          	$this->UserSettings->save($e_cv);
          	
          	$e_md = $this->{'UserSettings'}->newEmptyEntity();
          	$e_md->user_id    = $this->user_id;
            $e_md->name       = 'meshdesk_overview';
            $e_md->value      = 1;
          	$this->UserSettings->save($e_md);    	
      	}*/
         
        //===========
        //BASIC
        $d_profile_basic = [];
        $d_profile_basic['name']    	= $name;
        $d_profile_basic['cloud_id'] 	= $this->cloud_id;
        $e_profile_basic = $this->{'Profiles'}->newEntity($d_profile_basic);
        $this->{'Profiles'}->save($e_profile_basic);
        
        $d_pc_basic                             = [];
        $d_pc_basic['cloud_id']                 = $this->cloud_id;
        $d_pc_basic['name']                     = $this->prof_comp_prefix.$e_profile_basic->id;
        $e_pc_basic = $this->{'ProfileComponents'}->newEntity($d_pc_basic);
        $this->{'ProfileComponents'}->save($e_pc_basic);
        
        //We have to add this in order to 'bind' the profile to the user ... even if its empty
        $d_rad_ug               = [];
        $d_rad_ug['username']   = $name;
        $d_rad_ug['groupname']  = $this->prof_comp_prefix.$e_profile_basic->id;
        $d_rad_ug['priority']   = 5;
        
        $e_rad_ug = $this->{'Radusergroups'}->newEntity($d_rad_ug);
        $this->{'Radusergroups'}->save($e_rad_ug);
        
        
        //CLICK TO CONNECT
        $d_profile_c_t_c = [];
        $d_profile_c_t_c['name']     = $name.$this->ctc;
        $d_profile_c_t_c['cloud_id'] = $this->cloud_id;
        $e_profile_c_t_c = $this->{'Profiles'}->newEntity($d_profile_c_t_c);
        $this->{'Profiles'}->save($e_profile_c_t_c);
        
        $d_pc_c_t_c                             = [];
        $d_pc_c_t_c['cloud_id']                 = $this->cloud_id;
        $d_pc_c_t_c['name']                     = $this->prof_comp_prefix.$e_profile_c_t_c->id;
        $e_pc_c_t_c = $this->{'ProfileComponents'}->newEntity($d_pc_c_t_c);
        $this->{'ProfileComponents'}->save($e_pc_c_t_c);
        
        $d_rad_ug               = [];
        $d_rad_ug['username']   = $name.$this->ctc;
        $d_rad_ug['groupname']  = $this->prof_comp_prefix.$e_profile_c_t_c->id;
        $d_rad_ug['priority']   = 5;
        
        $e_rad_ug = $this->{'Radusergroups'}->newEntity($d_rad_ug);
        $this->{'Radusergroups'}->save($e_rad_ug);
        
        $d_reset = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'Rd-Reset-Type-Data',
            'op'        => ':=',
            'value'     => 'daily',
            'comment'   => 'SimpleProfile'
        ];
        $e_data_reset = $this->{'Radgroupchecks'}->newEntity($d_reset);
        $this->{'Radgroupchecks'}->save($e_data_reset);
    
        $d_amount = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'Rd-Total-Data',
            'op'        => ':=',
            'value'     => '250000000',
            'comment'   => 'SimpleProfile'
        ];
        $e_data_amount = $this->{'Radgroupchecks'}->newEntity($d_amount);
        $this->{'Radgroupchecks'}->save($e_data_amount);
        
        $d_cap = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'Rd-Cap-Type-Data',
            'op'        => ':=',
            'value'     => 'hard',
            'comment'   => 'SimpleProfile'
        ];
        $e_data_cap = $this->{'Radgroupchecks'}->newEntity($d_cap);
        $this->{'Radgroupchecks'}->save($e_data_cap);
       
        $d_mac = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'Rd-Mac-Counter-Data',
            'op'        => ':=',
            'value'     => '1',
            'comment'   => 'SimpleProfile'
        ];
        $e_data_mac = $this->{'Radgroupchecks'}->newEntity($d_mac);
        $this->{'Radgroupchecks'}->save($e_data_mac);
        
        $d_up = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'WISPr-Bandwidth-Max-Up',
            'op'        => ':=',
            'value'     => 512000,
            'comment'   => 'SimpleProfile'
        ];
        
        $e_up = $this->{'Radgroupreplies'}->newEntity($d_up);
        $this->{'Radgroupreplies'}->save($e_up);
        
        $d_down = [
            'groupname' => $this->prof_comp_prefix.$e_profile_c_t_c->id,
            'attribute' => 'WISPr-Bandwidth-Max-Down',
            'op'        => ':=',
            'value'     => 512000,
            'comment'   => 'SimpleProfile'
        ];
        
        $e_down = $this->{'Radgroupreplies'}->newEntity($d_down);
        $this->{'Radgroupreplies'}->save($e_down);
        
        
        //USER REGISTRATION
        $d_profile_reg = [];
        $d_profile_reg['name']     = $name.$this->reg;
        $d_profile_reg['cloud_id'] = $this->cloud_id;
        $e_profile_reg = $this->{'Profiles'}->newEntity($d_profile_reg);
        $this->{'Profiles'}->save($e_profile_reg);
        
        $d_pc_reg                             = [];
        $d_pc_reg['cloud_id']                 = $this->cloud_id;
        $d_pc_reg['name']                     = $this->prof_comp_prefix.$e_profile_reg->id;
        $e_pc_reg = $this->{'ProfileComponents'}->newEntity($d_pc_reg);
        $this->{'ProfileComponents'}->save($e_pc_reg);     
        
        $d_rad_ug               = [];
        $d_rad_ug['username']   = $name.$this->reg;
        $d_rad_ug['groupname']  = $this->prof_comp_prefix.$e_profile_reg->id;
        $d_rad_ug['priority']   = 5;
        
        $e_rad_ug_reg = $this->{'Radusergroups'}->newEntity($d_rad_ug);
        $this->{'Radusergroups'}->save($e_rad_ug_reg);
            
        
        //===========
             
        $d_user['profile']    = $e_profile_basic->name;
        $d_user['profile_id'] = $e_profile_basic->id;
        
        $d_user['realm']      = $this->new_name;
        $d_user['realm_id']   = $realm_id;
        
        $d_user['country_id'] = 4;
        $d_user['language_id']= 4; 
        $d_user['cloud_id']   = $this->cloud_id;
        
        //Make the username so that it has the suffix
        $d_user['username']   = $ap_name.'@'.$ap_name;
        $d_user['password']   = $this->pwd;
        $d_user['active']	  = 1;
         
        $e_pu = $this->PermanentUsers->newEntity($d_user);       
        $this->PermanentUsers->save($e_pu);
        $permanent_user_id =  $e_pu->id;
            
        $d_detail = $d_common;
         
        //===Auto Suffix==
        $d_detail['auto_suffix_check']      = 1;
        $d_detail['auto_suffix']            = $ap_name;  
        
        //==Registration==
        $d_detail['realm_id']   = $realm_id;
     
        
        $d_detail['profile_id']             = $e_profile_reg->id;

        $d_detail['reg_auto_suffix_check']  = 1;
        $d_detail['reg_auto_suffix']        = $ap_name;
        
        //Click to Connect
        $d_c_t_c                = [];
        $d_c_t_c['username']    = 'click_to_connect@'.$ap_name;
        $d_c_t_c['password']    = 'click_to_connect';
        $d_c_t_c['language_id'] = '4'; //*SPECIAL
        $d_c_t_c['active']      = 1;
        $d_c_t_c['country_id']  = 4;
        $d_c_t_c['cloud_id']    = $this->cloud_id; 
        $d_c_t_c['realm']       = $this->new_name;
        $d_c_t_c['realm_id']    = $realm_id;
              
        $d_c_t_c['profile']     = $e_profile_c_t_c->name;
        $d_c_t_c['profile_id']  = $e_profile_c_t_c->id; 
        $e_c_t_c                = $this->PermanentUsers->newEntity($d_c_t_c);   
        $this->PermanentUsers->save($e_c_t_c);
        $c_t_c_user_id          =  $e_c_t_c->id; 
     
             
        $e_d_detail = $this->DynamicDetails->newEntity($d_detail); 
        $this->DynamicDetails->save($e_d_detail);
        $dynamic_detail_id = $e_d_detail->id;
        
        $d_detail_ctcs                      = [];
        $d_detail_ctcs['dynamic_detail_id']= $dynamic_detail_id;
        $d_detail_ctcs['connect_check']     = 1;       
        $d_detail_ctcs['connect_username']  = 'click_to_connect';
        $d_detail_ctcs['connect_suffix']    = 'ssid';
        $e_d_detail_ctcs                    = $this->DynamicDetailCtcs->newEntity($d_detail_ctcs);
        $this->DynamicDetailCtcs->save($e_d_detail_ctcs);       
        
        //Add a sample background
        $source     = WWW_ROOT."img/dynamic_photos/sample.jpg";
        $dest       = WWW_ROOT."img/dynamic_photos/".$ap_name.".jpg";
        copy($source, $dest);
        
        $d_photo                        = array();
        $d_photo['dynamic_detail_id']   = $dynamic_detail_id;
        $d_photo['file_name']           = $ap_name.".jpg";
        $d_photo['title']               = "Sample Title";
        $d_photo['description']         = "Sample Description";
        
        $e_d_photo = $this->DynamicPhotos->newEntity($d_photo);
        $this->DynamicPhotos->save($e_d_photo);
           
        $e_ap_profile = $this->ApProfiles->newEntity($d_common);
        $this->ApProfiles->save($e_ap_profile);
        $ap_profile_id = $e_ap_profile->id;    
        $this->_complete_ap_profile($ap_profile_id,$realm_id,$dynamic_detail_id);
        
        $e_mesh = $this->Meshes->newEntity($d_common);
        $this->Meshes->save($e_mesh);
        $mesh_id = $e_mesh->id;
        $this->_complete_mesh($mesh_id,$realm_id,$dynamic_detail_id); 
    }
    
     private function _complete_ap_profile($ap_profile_id,$realm_id,$dynamic_detail_id){
        //Entry point Guest
        $d_ap_guest = array();
        $d_ap_guest['ap_profile_id']    = $ap_profile_id;
        $d_ap_guest['name']             = $this->ssid_guest;
        $d_ap_guest['isolate']          = 1;
        
        $e_ap_guest = $this->ApProfileEntries->newEntity($d_ap_guest);
        $this->ApProfileEntries->save($e_ap_guest);
        $entry_guest_id = $e_ap_guest->id;
        
        //Entry point Wireless
        $d_ap_wireless = array();
        $d_ap_wireless['ap_profile_id']    = $ap_profile_id;
        $d_ap_wireless['name']             = $this->ssid_wireless;
        $d_ap_wireless['encryption']       = 'psk2';
        $d_ap_wireless['special_key']      = $this->key_wireless;
        
        $e_ap_wireless = $this->ApProfileEntries->newEntity($d_ap_wireless);
        $this->ApProfileEntries->save($e_ap_wireless);
        $entry_wireless_id = $e_ap_wireless->id;
        
        //Exit point Guest
        $d_exit_guest                           = array();
        $d_exit_guest['type']                   = 'captive_portal';
        $d_exit_guest['ap_profile_id']          = $ap_profile_id;
        $d_exit_guest['auto_dynamic_client']    = 1;
        $d_exit_guest['auto_login_page']        = 1;
        $d_exit_guest['realm_list']             = $realm_id; //**
        $d_exit_guest['dynamic_detail_id']      = $dynamic_detail_id; //**
        
        $e_exit_guest   = $this->ApProfileExits->newEntity($d_exit_guest);
        $this->ApProfileExits->save($e_exit_guest);
        $exit_guest_id  = $e_exit_guest->id;
        
        //Exit point Wireless
        $d_exit_wireless                        = array();
        $d_exit_wireless['type']                = 'bridge';
        $d_exit_wireless['ap_profile_id']       = $ap_profile_id;
        
        $e_exit_wireless    = $this->ApProfileExits->newEntity($d_exit_wireless);   
        $this->ApProfileExits->save($e_exit_wireless);
        $exit_wireless_id   = $e_exit_wireless->id;
        
        //Now marry them.....
        $d_ap_m_guest = array();
        $d_ap_m_guest['ap_profile_exit_id']     = $exit_guest_id;
        $d_ap_m_guest['ap_profile_entry_id']    = $entry_guest_id;
        
        $e_ap_m_guest    = $this->ApProfileExitApProfileEntries->newEntity($d_ap_m_guest);
        $this->ApProfileExitApProfileEntries->save($e_ap_m_guest);
        
        $d_ap_m_wireless = array();
        $d_ap_m_wireless['ap_profile_exit_id']     = $exit_wireless_id;
        $d_ap_m_wireless['ap_profile_entry_id']    = $entry_wireless_id;
        
        $e_ap_m_wireless    = $this->ApProfileExitApProfileEntries->newEntity($d_ap_m_wireless);  
        $this->ApProfileExitApProfileEntries->save($e_ap_m_wireless);
        
        //Add an entry for the captive portal
        Configure::load('ApProfiles','default'); 
        $d_cp = $this->_default_captive_info();
        $d_cp['coova_optional']     = "ssid ".$this->ap_name."\n";
        $d_cp['ap_profile_exit_id'] = $exit_guest_id;
        $e_cp = $this->ApProfileExitCaptivePortals->newEntity($d_cp);
        $this->ApProfileExitCaptivePortals->save($e_cp);
    }
    
    private function _default_captive_info(){
        $data = [];       
        $this->loadModel('UserSettings');   
        $q_r = $this->{'UserSettings'}->find()->where(['user_id' => -1])->all();
        if($q_r){
            foreach($q_r as $s){
                //ALL Captive Portal Related default settings will be 'cp_<whatever>'
                if(preg_match('/^cp_/',$s->name)){
                    $name = preg_replace('/^cp_/', '', $s->name);
                    if(($name == 'mac_auth')||($name == 'swap_octet')){ //Binary flags must be translated to 0 or 1
                    	$data[$name]    = 1;	
                    }else{
                    	$data[$name]    = $s->value;
                    }                                             
                } 
            }
        }
        return $data;
    }
    
    private function _complete_mesh($mesh_id,$realm_id,$dynamic_detail_id){
        //Entry point Guest
        $d_mesh_guest = array();
        $d_mesh_guest['mesh_id']        = $mesh_id;
        $d_mesh_guest['name']           = $this->ssid_guest;
        $d_mesh_guest['isolate']        = 1;
        $d_mesh_guest['apply_to_all']   = 1;
        
        $e_mesh_guest = $this->MeshEntries->newEntity($d_mesh_guest);
        
        $this->MeshEntries->save($e_mesh_guest);
        $entry_guest_id = $e_mesh_guest->id;
             
        //Entry point Wireless
        $d_mesh_wireless = array();
        $d_mesh_wireless['mesh_id']     = $mesh_id;
        $d_mesh_wireless['name']        = $this->ssid_wireless;
        $d_mesh_wireless['encryption']  = 'psk2';
        $d_mesh_wireless['special_key'] = $this->key_wireless;
        $d_mesh_wireless['apply_to_all']= 1;
       
        $e_mesh_wireless    = $this->MeshEntries->newEntity($d_mesh_wireless);
        $this->MeshEntries->save($e_mesh_wireless);
        $entry_wireless_id  = $e_mesh_wireless->id;
        
        //Exit point Guest
        $d_exit_guest                   = array();
        $d_exit_guest['type']           = 'captive_portal';
        $d_exit_guest['mesh_id']        = $mesh_id;
        $d_exit_guest['auto_detect']    = 1;
         
        $e_exit_guest    = $this->MeshExits->newEntity($d_exit_guest);
        $this->MeshExits->save($e_exit_guest);
        $exit_guest_id   = $e_exit_guest->id;
 
        
        //Exit point Wireless
        $d_exit_wireless                = array();
        $d_exit_wireless['type']        = 'bridge';
        $d_exit_wireless['mesh_id']     = $mesh_id;
        $d_exit_wireless['auto_detect'] = 1;
        
        $e_exit_wireless    = $this->MeshExits->newEntity($d_exit_wireless);
        $this->MeshExits->save($e_exit_wireless);
        $exit_wireless_id   = $e_exit_wireless->id;
        
        //Now marry them.....
        $d_ap_m_guest = array();
        $d_ap_m_guest['mesh_exit_id']     = $exit_guest_id;
        $d_ap_m_guest['mesh_entry_id']    = $entry_guest_id;
 
        $e_ap_m_guest  = $this->MeshExitMeshEntries->newEntity($d_ap_m_guest);
        $this->MeshExitMeshEntries->save($e_ap_m_guest);
        
        $d_ap_m_wireless = array();
        $d_ap_m_wireless['mesh_exit_id']     = $exit_wireless_id;
        $d_ap_m_wireless['mesh_entry_id']    = $entry_wireless_id;
        $e_ap_m_wireless  = $this->MeshExitMeshEntries->newEntity($d_ap_m_wireless);
        $this->MeshExitMeshEntries->save($e_ap_m_wireless);
        
        //formulate the NAS ID (Mesh_name_mcp_exit_id)
        $mesh_name_underscored = preg_replace('/\s+/', '_', $this->new_name);
        $mesh_name_underscored = strtolower($mesh_name_underscored);
        $nas_id = $mesh_name_underscored.'_mcp_'.$exit_guest_id;
        $nas_id_short = 'mcp_'.$exit_guest_id;
         
        
        //Add an entry for the captive portal
        Configure::load('MESHdesk','default'); 
        $d_cp = $this->_default_captive_info();
        $d_cp['coova_optional']     = '';
        $d_cp['mesh_exit_id']       = $exit_guest_id;
        $d_cp['radius_nasid']       = $nas_id_short;
        $d_cp['coova_optional']     = "ssid ".$this->ap_name."\n";
        $e_cp  = $this->MeshExitCaptivePortals->newEntity($d_cp);  
        $this->MeshExitCaptivePortals->save($e_cp);
        
        //Dynamic RADIUS Client
        $d_common = [];
        $d_common['cloud_id']               = $this->cloud_id;
        $d_common['name']                   = 'MESHdesk_'.$nas_id;
        $d_common['nasidentifier']          = $nas_id_short;
        $d_common['available_to_siblings']  = 1;
        $d_common['type']            		= 'CoovaMeshdesk';
             

        $e_dc  = $this->DynamicClients->newEntity($d_common); 
        $this->DynamicClients->save($e_dc);
        $dynamic_client_id = $e_dc->id;
        
        $d_dc_r = array();
        $d_dc_r['dynamic_client_id'] = $dynamic_client_id;
        $d_dc_r['realm_id'] = $realm_id;
        $e_dc_r  = $this->DynamicClientRealms->newEntity($d_dc_r);
        $this->DynamicClientRealms->save($e_dc_r);
        $dynamic_client_realm_id = $e_dc_r->id;
        
        //Key Value Pair for Dynamic Login Page
        $d_p = [];
        $d_p['name']    = 'nasid';
        $d_p['value']   = $nas_id_short;
        $d_p['priority']= 1;
        $d_p['dynamic_detail_id'] = $dynamic_detail_id;
        $e_d_p  = $this->DynamicPairs->newEntity($d_p);   
        $this->DynamicPairs->save($e_d_p);    
    }
     
    private function _test_if_exists($name){
    
        $tables_to_check = [
            'Realms'            => 'Realm',
            'DynamicDetails'    => 'Dynamic Login Page',
            'DynamicClients'    => 'Dynamic RADIUS Client',
            'ApProfiles'        => 'ApProfile',
            'Meshes'            => 'Mesh Network',
            'Profiles'          => 'Profile',
            'Clouds'			=> 'Cloud'
        ];
        
        foreach(array_keys($tables_to_check) as $key){
            $count = $this->{$key}->find()->where(['name' => $name])->count();
            if($count > 0){
                return $tables_to_check[$key];
            }
        }
           
        //Access Provder 
        $ap_name = preg_replace('/\s+/', '_', $this->new_name);
        $ap_name = strtolower($ap_name);
        
        if($this->{'PermanentUsers'}->find()->where(['PermanentUsers.username' => $ap_name.'@'.$ap_name])->count() > 0){  
            return 'Permanent User';
        }
        
        if($this->{'PermanentUsers'}->find()->where(['PermanentUsers.username' => 'click_to_connect@'.$ap_name])->count() > 0){
            return 'Permanent User';
        }
        
        return false;
    }
     
    private function _make_linux_password($pwd){
		return exec("openssl passwd -1 $pwd");
	}
    
    private function _return_cloud_id_from_name($name,$user_id){
    	//We use a combination of $user_id (owner) and name...	

        $qr             = $this->Clouds->find()->where(['Clouds.name' => $name,'Clouds.user_id'=> $user_id])->first();      
        $id = false; 
        if($qr){
            $id = $qr->id;
        }
	    return $id;
	}	
}

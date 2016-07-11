<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;


use App\Models\Lead;
use App\Models\SphereAttr;
use App\Models\SphereAttrOptions;

class TaskController extends Controller
{
    public function index(){
		if(isset(\Sentinel::getUser()->id)){
		$leads = \DB::table('leads')
		    ->join('customers', 'leads.customer_id', '=', 'customers.id')
		    ->join('open_leads', function ($join) {
            $join->on('leads.id', '=', 'open_leads.lead_id')->where('open_leads.agent_id', '=', ''.\Sentinel::getUser()->id.'');
			})
            ->select('leads.id','leads.date','leads.name','leads.email','customers.phone')
            ->get();

        return view('task', ['leads' => $leads]);
		}
		else{
		return redirect('auth/login');
		}
    }
	
    public function detail(){
		
		if ( $_POST ) {
            $id = $_POST['id'];
		 $leads = Lead::where('leads.id', '=', $id)
		    ->join('customers', 'leads.customer_id', '=', 'customers.id')
		    ->join('open_leads', 'leads.id', '=', 'open_leads.lead_id')
            ->select('leads.id','leads.date','leads.name','leads.email','customers.phone')
            ->first();
			
			$sphere_id = Lead::select('sphere_id')->where('leads.id', '=', $id)->first()['sphere_id'];
            $sphere_bitmask = 'sphere_bitmask_'.$sphere_id;
			
			$mask_SphereAttr_radio=SphereAttr::where('sphere_attributes._type', '=', 'radio')
					->join('sphere_attribute_options', 'sphere_attributes.id','=','sphere_attribute_options.sphere_attr_id')
					->select('sphere_attributes.id')
					->first()['id'];
			
			$l['radio']=Lead::where('leads.id', '=', $id)
			->join('sphere_attributes', function ($join){
			   $join->on('leads.sphere_id', '=', 'sphere_attributes.sphere_id')
			   ->where('sphere_attributes._type', '=', 'radio');
			})
            ->join('sphere_attribute_options', function ($join){
                    $join->on('sphere_attributes.id', '=', 'sphere_attribute_options.sphere_attr_id')
                        ->where('sphere_attribute_options.ctype', '=', 'agent');
                })
			->join($sphere_bitmask, function ($join){
                    $join->on( 'user_id', '=', 'leads.id')
                        ->where('type', '=', 'lead');
                })
                ->select('sphere_attribute_options.value','sphere_attribute_options.id as options_id','label',''.$sphere_bitmask.'.*')	
			    ->get(); 
			
			$res['radio']='';
			foreach ($l['radio'] as $ll)
			{
				$k='fb_'.$mask_SphereAttr_radio.'_'.$ll->options_id;
				if(($ll->$k)==1){$res['radio'].=' '.$ll->value;}
			}
			
			$mask_SphereAttr_checkbox=SphereAttr::where('sphere_attributes._type', '=', 'checkbox')
					->join('sphere_attribute_options', 'sphere_attributes.id','=','sphere_attribute_options.sphere_attr_id')
					->select('sphere_attributes.id')
					->first()['id'];
			
				$l['checkbox']=Lead::where('leads.id', '=', $id)
			->join('sphere_attributes', function ($join){
			   $join->on('leads.sphere_id', '=', 'sphere_attributes.sphere_id')
			   ->where('sphere_attributes._type', '=', 'checkbox');
			})
            ->join('sphere_attribute_options', function ($join){
                    $join->on('sphere_attributes.id', '=', 'sphere_attribute_options.sphere_attr_id')
                        ->where('sphere_attribute_options.ctype', '=', 'agent');
                })
			->join($sphere_bitmask, function ($join){
                    $join->on( 'user_id', '=', 'leads.id')
                        ->where('type', '=', 'lead');
                })
                ->select('sphere_attribute_options.value','sphere_attribute_options.id as options_id','label',''.$sphere_bitmask.'.*')	
			    ->get(); 
			
			$res['checkbox']='';
			foreach ($l['checkbox'] as $ll)
			{
				$k='fb_'.$mask_SphereAttr_checkbox.'_'.$ll->options_id;
				if(($ll->$k)==1){$res['checkbox'].=' '.$ll->value;}
			}
			
			$result='
			<table class="table table-bordered table-hover">
			<tr>
			<td>icon</td><td></td>
			</tr>
			<tr>
			<td>date</td><td>'.$leads->date.'</td>
			</tr>
			<tr>
			<td>name</td><td>'.$leads->name.'</td>
			</tr>
			<tr>
			<td>phone</td><td>'.$leads->phone.'</td>
			</tr>
			<tr>
			<td>email</td><td>'.$leads->email.'</td>
			</tr>
			<tr>
			<td>Radio</td><td>'.$res['radio'].'</td>
			</tr>
			<tr>
			<td>Checkbox</td><td>'.$res['checkbox'].'</td>
			</tr>
			</table>
			';
			
			return \Response::json($result);
        }
		
    }

}

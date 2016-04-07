<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\CharacteristicOptions;
use Illuminate\Support\Facades\Input;
use App\Models\CharacteristicGroup;
use App\Models\Characteristics;
use App\Models\CharacteristicBit;
use Illuminate\Http\Request;
use Datatables;

class CharacteristicsController extends AdminController {
    public function __construct()
    {
        view()->share('type', 'characteristics');
    }
    /*
   * Display a listing of the resource.
   *
   * @return Response
   */
    public function index()
    {
        // Show the page
        return view('admin.characteristics.index');
    }

    /**
     * Show the form for edit the resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        return view('admin.characteristics.create_edit')->with('fid',$id);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.characteristics.create_edit')->with('fid',0);
    }

    /**
     * Send config to page builder for creating/editing a resource.
     *
     * @return JSON
     */
    public function get_config($id)
    {
        $data = [
            "renderType"=>"dynamicForm",
            "id"=>null,
            "targetEntity"=>"Characteristic",
            "values"=>[],
            "settings"=>[
                "view"=>[
                    "show"=>"form.dynamic",
                    "edit"=>"modal.dynamic"
                ],
                "form.dynamic"=>[],
                "button"=>"Add field"
            ]
        ];
        $opts = [
            "renderType"=>"single",
            'name' => 'text',
            'values'=>'',
            "attributes" => [
                "type"=>'text',
                "class" => 'form-control',
            ],
            "settings"=>[
                "label" => 'Form name',
                "type"=>'text'
            ],
        ];
        if($id) {
            $group = CharacteristicGroup::find($id);
            $data['id']=$id;
            $opts['values'] = $group->name;

            foreach($group->characteristics()->get() as $chrct) {
                $arr=[];
                $arr['id'] = $chrct->id;
                $arr['_type'] = $chrct->_type;
                $arr['label'] = $chrct->label;
                $arr['requiered'] = ($chrct->requiered)?1:0;
                $arr['position'] = $chrct->position;
                if($chrct->has('options')) {
                    $arr['option']=[];
                    foreach($chrct->options()->get() as $eav) {
                        $arr['option'][]=['id'=>$eav->id,'val'=>$eav->name];
                    }
                }
                $data['values'][]=$arr;
            }
        }

        $data=['opt'=>$opts,"cform"=>$data];
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return
     * Response
     */
    public function store(Request $request)
    {
        return false;
    }

    /**
     * Create a newly created, update existing resource in storage.
     *
     * @return Response
     */
    public function update(Request $request,$id)
    {
        $opt = $request->only('opt');
        if($id) {
            $group = CharacteristicGroup::find($id);
            $group->name = $opt['opt']['data'];
        } else {
            $group = new CharacteristicGroup(['name' => $opt['opt']['data']]);
            $group->save();
        }
        $bitMask = new CharacteristicBit($group->id);
        $group->table_name = $bitMask->getTableName();
        $group->save();

        $data = $request->only('cform');

        $new_chr = [];
        foreach($data['cform']['data']['variables'] as $attr) {
            if (isset($attr['id']) && $attr['id']) { $new_chr[]=$attr['id']; }
        }
        $old_chr = $group->characteristics()->lists('id')->all();
        if ($deleted = (array_diff($old_chr, $new_chr))) {
            $group->characteristics()->whereIn('id', $deleted)->delete(); /// Need small fix!!!!!!!!!!!!!!!!!!!!!!
            $bitMask->removeAttr($deleted,null);
        }

        foreach($data['cform']['data']['variables'] as $attr) {
            if (isset($attr['id']) && $attr['id']) {
                $characteristic = Characteristics::find($attr['id']);
                $characteristic->update($attr);
            } else {
                $characteristic = new Characteristics($attr);
                $group->characteristics()->save($characteristic);
            }
            $new_options = [];
            for ($i = 0; $i < count($attr['option']); $i++) {
                if($attr['option'][$i]['id']) $new_options[] = $attr['option'][$i]['id'];
            }

            $old_options = $characteristic->options()->lists('id')->all();
            if ($deleted = (array_diff($old_options, $new_options))) {
                $characteristic->options()->whereIn('id', $deleted)->delete();
                $bitMask->removeAttr($characteristic->id, $deleted);
            }

            foreach ($attr['option'] as $optVal) {
                if ($optVal['id']) {
                    $chr_options = CharacteristicOptions::find($optVal['id']);
                    $chr_options->name = $optVal['val'];
                    $chr_options->save();
                } else {
                    $chr_options = new CharacteristicOptions();
                    $chr_options->name = $optVal['val'];
                    $characteristic->options()->save($chr_options);
                    $bitMask->addAttr($characteristic->id, $chr_options->id);
                }
            }
        }
        return response()->json(TRUE);
    }

    /**
     * Show a list of all the languages posts formatted for Datatables.
     *
     * @return Datatables JSON
     */
    public function data()
    {
        $chr = CharacteristicGroup::select(['id', 'name', 'table_name', 'created_at']);

        return Datatables::of($chr)
            ->add_column('actions', '<a href="{{ route(\'admin.characteristics.edit\',[$id]) }}" class="btn btn-success btn-sm" ><span class="glyphicon glyphicon-pencil"></span>  {{ trans("admin/modal.edit") }}</a>
                    <a href="{{{ URL::to(\'admin/user/\' . $id . \'/delete\' ) }}}" class="btn btn-sm btn-danger"><span class="glyphicon glyphicon-trash"></span> {{ trans("admin/modal.delete") }}</a>')
            ->remove_column('id')
            ->make();
    }
}
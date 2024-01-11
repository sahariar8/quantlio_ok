<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\PermissionGroup;
use DB;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
        $this->middleware('permission:insert-role', ['only' => ['create']]);
        $this->middleware('permission:edit-role', ['only' => ['edit','update']]);
        $this->middleware('permission:delete-role', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(){
        $roles = Role::all();
        $permission = Permission::get();
        $permissionGroup = PermissionGroup::all();
      
        foreach($permissionGroup as $key => $value) {
            $permissionGroup[$key]->permissions = Permission::select('*')->where('groupedList_id', $value->id)->get();
        }
        return view('roles', compact('roles','permissionGroup'))->with('i');
    }
    public function edit($id){
        $role = Role::find($id);
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
        return response()->json([
            'status' => 200,
            'role' => $role,
            'rolePermissions' => $rolePermissions,
        ]);
    }
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:roles,name,'.$id,
        ]);
        if($validator->fails()){
            return redirect('/roles')->with('validation_error', $id)->withErrors($validator)->withInput();
        }else{
            $roleId  = $request->input('role_id');
            $role = Role::find($roleId);
            $role->name = $request->input('name');
            if ($role->update()) {
                $role->syncPermissions($request->input('permissionValues'));
                return redirect('/roles')->with('save_success', 'Role updated successfully.');
            } else {
                return redirect('/roles')->with('save_error', 'There is some problem while saving!');
            }
        }
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:roles,name',
        ]);
        if($validator->fails()){
            return redirect('/roles')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }else{
            $role = new Role;
            $role->name = $request->input('name');
            $role->syncPermissions($request->input('permissionValues'));
            if ($role->save()) {
                return redirect('/roles')->with('save_success', 'Role created successfully.');
            } else {
                return redirect('/roles')->with('save_error', 'There is some problem while saving!');
            }
        }
    }
    public function destroy(Request $request){
        $roleId  = $request->input('delete_role_id');
        $role = Role::find($roleId);
        if ($role->delete()) {
            return redirect('/roles')->with('save_success', 'Role deleted successfully.');
        } else {
            return redirect('/roles')->with('save_error', 'There is some problem while Deleting!');
        }
    } 
}

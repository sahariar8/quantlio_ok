<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Section;
use App\Models\TestDetail;
use App\Models\User;
use App\Models\CommentSection;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use DB;

class CommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        //  $this->middleware('permission:comments|insert-comment|edit-comment|delete-comment', ['only' => ['show','show']]);
        $this->middleware('permission:insert-comment', ['only' => ['create']]);
        $this->middleware('permission:edit-comment', ['only' => ['edit','update']]);
        $this->middleware('permission:delete-comment', ['only' => ['destroy']]);
    }
    public function index(){
        $comments = Comment::all();
        $sections = Section::all();
        $testDetails = TestDetail::all();
        return view('commentList', compact('comments','sections','testDetails'))->with('i');
    }
     public function create(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'section' => 'required|not_in:0',
                'inputTestName' => 'required|not_in:0',
                'inputComment' => 'required',
            ]);
            if($validator->fails()){
                return redirect('/comments')->with('validation_error', ' ')->withErrors($validator)->withInput();
            }else{
                $comment = new Comment;
                $comment->test_id = $request->input('inputTestName');
                $comment->comment = $request->input('inputComment');
                
                if ($comment->save()) {
                    
                    $sectionIds = $request->input('section');
                    $commentSections = [];
                    foreach($sectionIds as $sectionId) {
                        $commentSections[] = [
                            'comment_id' => $comment->id,
                            'section_id' => $sectionId,
                        ];
                    }
                    CommentSection::insert($commentSections);
                    return redirect('/comments')->with('save_success', 'Comment added successfully.');
                } else {
                    return redirect('/comments')->with('save_error', 'There is some problem while saving !');
                }
            }
        }catch (\Exception $e) {
            return redirect('/comments')->with('save_error', __('Internal server Error.'));
        } 
    } 
    public function edit($id){
        $comment = Comment::find($id);
        $commentSection = CommentSection::select('section_id')
            ->where('comment_id', '=', $id)
            ->pluck('section_id');
        return response()->json([
            'status' => 200,
            'comments' => $comment,
            'comment_section' => $commentSection
        ]);
    }
    public function update(Request $request, $id){
        try{
            $validator = Validator::make($request->all(),[
                'section' => 'required|not_in:0',
                'inputTestName' => 'required|not_in:0',
                'inputComment' => 'required',
            ]);
            if($validator->fails()){
                return redirect('/comments')->with('validation_error', $id)->withErrors($validator);
            }else{
                
                $commentId  = $request->input('comment_id');
                $comment = Comment::find($commentId);
                $comment->comment = $request->input('inputComment');
                $comment->test_id = $request->input('inputTestName');

                if ($comment->update()) {
                    DB::table('comments_has_sections')->where('comment_id', $commentId)->delete();
                    $sectionIds = $request->input('section');
                    $commentSections = [];
                    foreach($sectionIds as $sectionId) {
                        $commentSections[] = [
                            'comment_id' => $comment->id,
                            'section_id' => $sectionId,
                        ];
                    }
                    CommentSection::insert($commentSections);
                    return redirect('/comments')->with('save_success', 'Comment updated successfully.');
                } else {
                    return redirect('/comments')->with('save_error', 'There is some problem while saving!');
                }
            }
        }catch (\Exception $e) {
            return redirect('/comments')->with('save_error', __('Internal server Error.'));
        }
    }
    public function destroy(Request $request){
        $commentId  = $request->input('delete_comment_id');
        $comment = Comment::find($commentId);
        
        if ($comment->delete()) {
            DB::table('comments_has_sections')->where('comment_id', $commentId)->delete();
            return redirect('/comments')->with('save_success', 'Comment deleted successfully.');
        } else {
            return redirect('/comments')->with('save_error', 'There is some problem while Deleting!');
        }
    } 
}

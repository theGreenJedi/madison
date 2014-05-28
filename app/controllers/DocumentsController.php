<?php

class DocumentsController extends Controller
{
	public function listDocuments()
	{
		if(!Auth::check()) {
			return Redirect::to('/')->with('error', 'You must be logged in');
		}
		
		$docs = Doc::allOwnedBy(Auth::user()->id);
		
		return View::make('documents.list', compact('docs'));
	}
	
	public function saveDocumentEdits($documentId)
	{
		if(!Auth::check()) {
			return Redirect::to('documents')->with('error', 'You must be logged in');
		}
		
		$content = Input::get('content');
		$contentId = Input::get('content_id');
		
		if(empty($content)) {
			return Redirect::to('documents')->with('error', "You must provide content to save");
		}
		
		if(!empty($contentId)) {
			$docContent = DocContent::find($contentId);
		} else {
			$docContent = new DocContent();
		}
		
		if(!$docContent instanceof DocContent) {
			return Redirect::to('documents')->with('error', 'Could not locate document to save');
		}
		
		$document = Doc::find($documentId);
		
		if(!$document instanceof Doc) {
			return Redirect::to('documents')->with('error', "Could not locate the document");
		}
		
		if(!$document->canUserEdit(Auth::user())) {
			return Response::error('401');
		}
		
		$docContent->doc_id = $documentId;
		$docContent->content = $content;
		
		try {
			DB::transaction(function() use ($docContent, $content, $documentId, $document) {
				$docContent->save();
				$document->indexContent($docContent);
			});
		} catch(\Exception $e) {
			return Redirect::to('documents')->with('error', "There was an error saving the document: {$e->getMessage()}");
		}
		
		return Redirect::to('documents')->with('success_message', 'Document Saved Successfully');
	}
	
	public function editDocument($documentId)
	{
		if(!Auth::check()) {
			return Redirect::to('/')->with('error', 'You must be logged in');
		}
		
		$doc = Doc::find($documentId);
		
		if(is_null($doc)) {
			return Response::error('404');
		}
		
		if(!$doc->canUserEdit(Auth::user())) {
			return Response::error('401');
		}
		
		return View::make('documents.edit', array(
			'page_id' => 'edit_doc',
			'page_title' => "Editing {$doc->title}",
			'doc' => $doc,
			'contentItem' => $doc->content()->where('parent_id')->first()
		));
	}
	
	public function createDocument()
	{
		if(!Auth::check()) {
			return Redirect::to('/')->with('error', 'You must be logged in');
		}
		
		$input = Input::all();
		
		$rules = array(
			'title' => 'required'
		);
		
		$validator = Validator::make($input, $rules);
		 
		if($validator->fails()) {
			return Redirect::to('documents')->withInput()->withErrors($validator);
		}
		
		try {
			
			$docOptions = array(
				'title' => $input['title']
			);
			
			$user = Auth::user();
			
			$activeGroup = Session::get('activeGroupId');
			
			if($activeGroup > 0) {
				
				$group = Group::where('id', '=', $activeGroup)->first();
				
				if(!$group) {
					return Redirect::to('documents')->withInput()->with('error', 'Invalid Group');
				}
				
				if(!$group->userHasRole($user, Group::ROLE_EDITOR) && !$group->userHasRole($user, Group::ROLE_OWNER)) {
					return Redirect::to('documents')->withInput()->with('error', 'You do not have permission to create a document for this group');
				}
				
				$docOptions['sponsor'] = $activeGroup;
				$docOptions['sponsorType'] = Doc::SPONSOR_TYPE_GROUP;
			
			} else {
				
				if(!$user->can('independent_author_create_doc')) {
					return Redirect::to('documents')->withInput()->with('error', 'You do not have permission to create a document as an individual');
				}
				
				$docOptions['sponsor'] = Auth::user()->id;
				$docOptions['sponsorType'] = Doc::SPONSOR_TYPE_INDIVIDUAL;
			}
			
			$document = Doc::createEmptyDocument($docOptions);
			
			return Redirect::to("documents/edit/{$document->id}")->with('success_message', "Document Created Successfully");
		
		} catch(\Exception $e) {
			return Redirect::to("documents")->withInput()->with('error', "Sorry there was an error processing your request - {$e->getMessage()}");
		}
	}
}
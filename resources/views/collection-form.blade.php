@extends('layouts.app',['class'=> 'off-canvas-sidebar'])

@section('content')

<link rel="stylesheet"  href="http://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.min.css" type="text/css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script type="text/javascript">

$(document).ready(function() {
        //alert("js is working");
        src = "{{ route('autocomplete') }}";
        $( "#maintainer" ).autocomplete({
            source: function( request, response ) {
                $.ajax({
                    url: src,
                    method: 'GET',
                    dataType: "json",
                    data: {
                        term : request.term
                    },
                    success: function(data) {
                        //console.log(data);
                        response(data);
                    }
                });
            },
            minLength: 1,
        });
    });
</script>


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
		@if (empty($collection->id))
                <div class="card-header card-header-primary"><h4 class="card-title">Add Collection</h4></div>
		@else
                <div class="card-header card-header-primary"><h4 class="card-title">Edit Collection</h4></div>
		@endif

                <div class="card-body">
		<div class="row">
                  <div class="col-md-12 text-right">
                      <a href="/admin/collectionmanagement" class="btn btn-sm btn-primary" title="Back to List"><i class="material-icons">arrow_back</i></a>
                  </div>
                </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      	<i class="material-icons">close</i>
                    	</button>
                    	<span>{{ session('status') }}</span>
                        </div>
                    @endif

                   <form method="post" action="/admin/savecollection">
                    @csrf()
                    <input type="hidden" name="collection_id" value="{{$collection->id}}" />
                   <div class="form-group row">
                    <div class="col-md-4">
                   <label for="collection_name" class="col-md-8 col-form-label text-md-right">Name</label> 
                    </div>
                    <div class="col-md-6">
                    <input type="text" name="collection_name" id="collection_name" class="form-control" placeholder="Give your collection a name" value="{{ $collection->name }}" required />
                    </div>
                   </div>
                   <div class="form-group row">
                    <div class="col-md-4">
                   <label for="description" class="col-md-8 col-form-label text-md-right">Description</label> 
			</div>
                    <div class="col-md-6">
                    <textarea name="description" id="description" class="form-control" value="" placeholder="Description" required >{{ $collection->description }}</textarea>
                    </div>
                   </div>
                   <div class="form-group row">
                    <div class="col-md-4">
                   <label for="collection_type" class="col-md-8 col-form-label text-md-right">Type</label> 
			</div>
                    <div class="col-md-6">
                    <input type="checkbox" id="collection_type" name="collection_type" value="Members Only" 
                    @if($collection->type == 'Members Only')
                     checked 
                    @endif
                    /> Members Only
                    </div>
                   </div>
                   <div class="form-group row">
                    <div class="col-md-4">
                   <label for="maintainer" class="col-md-8 col-form-label text-md-right">Maintainer</label> 
			</div>
                    <div class="col-md-6">
                    <input type="text" name="maintainer" id="maintainer" class="form-control" value="@if(!empty($collection->maintainer()->email)){{$collection->maintainer()->email}}@endif" placeholder="Email ID of the maintainer">
                    </div>
                   </div>
                   <div class="form-group row">
                    <div class="col-md-4">
                   <label for="maintainer" class="col-md-8 col-form-label text-md-right">Document Type</label> 
			</div>
                    <div class="col-md-6">
                    <input type="checkbox" id="require_approval" name="require_approval" value="1" 
		@if($collection->require_approval == 1)
                     checked
                    @endif
                    /> Document becomes available after approval
	
                    </div>
                   </div>
                
                   <div class="form-group row mb-0"><div class="col-md-8 offset-md-4"><button type="submit" class="btn btn-primary">
                                    Save
                                </button> 
                     </div></div> 
                   </form> 
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

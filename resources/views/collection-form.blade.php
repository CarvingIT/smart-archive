@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Add Collection</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                   <form method="post" action="/admin/savecollection">
                    @csrf()
                   <div class="form-group row">
                   <label for="collection_name" class="col-md-4 col-form-label text-md-right">Name</label> 
                    <div class="col-md-6">
                    <input type="text" name="collection_name" id="collection_name" class="form-control" value="" placeholder="Give your collection a name" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <label for="description" class="col-md-4 col-form-label text-md-right">Description</label> 
                    <div class="col-md-6">
                    <textarea name="description" id="description" class="form-control" value="" placeholder="Description"></textarea>
                    </div>
                   </div>
                   <div class="form-group row">
                   <label for="collection_type" class="col-md-4 col-form-label text-md-right">Type</label> 
                    <div class="col-md-6">
                    <input type="checkbox" id="collection_type" name="collection_type" value="Members Only" /> Members Only
                    </div>
                   </div>
                   <div class="form-group row">
                   <label for="maintainer" class="col-md-4 col-form-label text-md-right">Maintainer</label> 
                    <div class="col-md-6">
                    <input type="text" name="maintainer" id="maintainer" class="form-control" value="" placeholder="ID of the maintainer"></textarea>
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

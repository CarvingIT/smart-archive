@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#documents').DataTable({
    "aoColumnDefs": [
           { "bSortable": false, "aTargets": [0, 4]},
           { "className": 'dt-right', "aTargets": [2]},
           { "className": 'dt-right', "aTargets": [3]},
           { "className": 'td-actions text-right', "aTargets": [4]}
     ],
    "order": [[ 3, "desc" ]],
    "serverSide":true,
    "ajax":'/collection/{{$collection->id}}/search',
    "columns":[
       {data:"type"},
       {data:"title"},
       {data:"size",
           render:{
             '_': 'display',
             'sort': 'bytes'
            }
        },
        {data:"updated_at",
            render:{
               '_':'display',
              'sort': 'updated_date'
            }
        },
        {data:"actions"},
    ]
    });
} );
</script>

<div class="container" style="margin-top:5%;">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
		<div class="card-header card-header-primary">
                <h4 class="card-title ">
            	<a href="/collections">Collections</a> :: {{ $collection->name }}
		</h4>
                  <!--div class="card-header-corner" style="margin-right:-65%; margin-top:-4%;">
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'MAINTAINER'))
                    <a href="/collection/{{ $collection->id }}/users"><img class="icon" src="/i/man-user.png" title="Manage users of this collection" style="width:3%;"/></a>
                    <a href="/collection/{{ $collection->id }}/meta"><img class="icon" src="/i/meta.png" title="Manage meta information fields of this collection" style="width:3%;"/></a>
                  @endif
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'CREATE'))
                    <a href="/collection/{{ $collection->id }}/upload"><img class="icon" src="/i/new-document.png" title="New document" style="width:3%;"/></a>
                  @endif
                  @if(count($collection->meta_fields)>0)
                    <a href="/collection/{{ $collection->id }}/metasearch"><img class="icon" src="/i/meta_search.png" title="Meta search" /></a>
                  @endif
                  </div-->
            </div>
                 <div class="card-body">
		<div class="row">
                  <div class="col-12 text-right">
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'MAINTAINER'))
                    <a title="Manage Users of this collection"href="/collection/{{ $collection->id }}/users" class="btn btn-sm btn-primary">{{ __('Manage users') }}</a>
                    <a title="Manage meta information fields of this collection" href="/collection/{{ $collection->id }}/meta" class="btn btn-sm btn-primary">{{ __('Manage Meta Info') }}</a>
		  @endif
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'CREATE'))
                    <a title="New Document" href="/collection/{{ $collection->id }}/upload" class="btn btn-sm btn-primary">{{ __('Add Document') }}</a>
		  @endif
                  @if(count($collection->meta_fields)>0)
                    <a href="/collection/{{ $collection->id }}/metasearch" title="Meta search" class="btn btn-sm btn-primary">Meta Search </a>
                  @endif
                  </div>
                </div>

                    <div class="flash-message">
                    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
                        @if(Session::has('alert-' . $msg))
                        <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}</p>
                        @endif
                    @endforeach
                    </div>
                    <p>{{ $collection->description }}</p>
                    <table id="documents" class="table">
                        <thead class="text-primary">
                            <tr>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                            </tr>
                        </thead>

                    </table>
                 </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

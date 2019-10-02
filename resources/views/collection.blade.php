@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
            <div class="card-header">{{ $collection->name }}
                  @if(!empty(Auth::user()->id))
                  <div style="float:right;"><a href="/collection/{{ $collection->id }}/upload">Add</a></div></div>
                  @endif
                  <div class="card-body">
                    {{ $collection->description }}
                 </div>
            </div>
            <div class="card">
            <div class="card-header">Documents</div>
                 <div class="card-body">
                    <ul>
                    @foreach($documents as $d)
                    <li><a href="/document/{{$d->id}}" target="_new">{{ $d->filename }}</a></li> 
                    @endforeach
                    </ul>
                    {{$documents->links() }}
                 </div>
            </div>
        </div>
    </div>
</div>
@endsection

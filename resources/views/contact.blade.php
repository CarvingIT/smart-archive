@extends('layouts.app',['class' => 'off-canvas-sidebar','title'=>'Smart Repository','activePage'=>'contact','titlePage'=>'Contact Us'])

@section('content')
<div class="container">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-primary"><h4 class="card-title">Contact Us</h4></div>

                <div class="card-body">
                    <p>If you would like to request a demo, have any questions or suggest a feature, we shall be glad to hear from you!</p>
                    <p>
                    Email: <a href="mailto:{{ env('CONTACT_EMAIL') }}">{{ env('CONTACT_EMAIL') }}</a><br/>
                    Phone: {{ env('CONTACT_PHONE') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

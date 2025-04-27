{{-- resources/views/rooms/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Lista e Dhomave</h2>
        <div class="row">
            @foreach ($rooms as $room)
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="{{ asset('images/rooms/' . $room->image) }}" alt="{{ $room->name }}" class="card-img-top" width="300">
                        <div class="card-body">
                            <h5 class="card-title">{{ $room->name }}</h5>
                            <p class="card-text">{{ $room->description }}</p>
                            <p class="card-text"><strong>Kapaciteti:</strong> {{ $room->capacity }} persona</p>
                            <p class="card-text"><strong>Çmimi:</strong> €{{ $room->price }}</p>
                            <a href="{{ route('rooms.show', $room->id) }}" class="btn btn-primary">Shiko detaje</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

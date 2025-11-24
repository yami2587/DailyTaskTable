@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Create Team</h2>

        <form method="POST" action="{{ route('team.store') }}">
            @csrf
            <label>Team Name</label>
            <input class="input-field" name="team_name" value="{{ old('team_name') }}" required>

            <label>Description</label>
            <textarea class="input-field" name="description">{{ old('description') }}</textarea>

            <button class="btn" type="submit">Save</button>
        </form>
    </div>
@endsection
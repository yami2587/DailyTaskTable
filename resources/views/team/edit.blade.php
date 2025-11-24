@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Edit Team</h2>

    <form method="POST" action="{{ route('team.update', $team->id) }}">
        @csrf @method('PUT')
        <label>Team Name</label>
        <input class="input-field" name="team_name" value="{{ old('team_name', $team->team_name) }}" required>

        <label>Description</label>
        <textarea class="input-field" name="description">{{ old('description', $team->description) }}</textarea>

        <button class="btn" type="submit">Update</button>
    </form>
</div>
@endsection

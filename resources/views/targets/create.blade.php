@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Create Target</h2>

        <form method="POST" action="{{ route('targets.store') }}">
            @csrf
            <label>Task</label>
            <select class="input-field" name="task_id" required>
                <option value="">-- choose task --</option>
                @foreach($tasks as $t)
                    <option value="{{ $t->id }}">{{ $t->task_title }}</option>
                @endforeach
            </select>

            <label>Title</label>
            <input class="input-field" name="title" required>

            <label>Remark</label>
            <textarea class="input-field" name="remark"></textarea>

            <label>Target Date</label>
            <input class="input-field" type="date" name="target_date" value="{{ date('Y-m-d') }}" required>

            <button class="btn" type="submit">Create</button>
        </form>
    </div>
@endsection
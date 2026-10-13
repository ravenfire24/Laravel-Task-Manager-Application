@csrf
<div class="form-group">
    <label for="title">Task title <span class="text-danger" aria-hidden="true">*</span>
    </label>
    <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}" class="form-control
    @error('title')
        is-invalid
    @enderror
    " maxlength="255" required autofocus
    @error('title')
        aria-describedby="title-error" aria-invalid="true"
    @enderror
    >

    @error('title')
        <div id="title-error" class="invalid-feedback">{{ $message }}</div>
    @enderror

</div>
<div class="form-group">
    <label for="description">Description <span class="text-muted font-weight-normal">(optional)</span>
    </label>
    <textarea id="description" name="description" rows="5" maxlength="10000" class="form-control @error('description') is-invalid @enderror" @error('description') aria-describedby="description-error" aria-invalid="true" @enderror>{{ old('description', $task->description) }}</textarea>

    @error('description')
        <div id="description-error" class="invalid-feedback">{{ $message }}</div>
    @enderror

</div>
<div class="form-group">
    <label for="status">Status <span class="text-danger" aria-hidden="true">*</span>
    </label>
    <select id="status" name="status" required class="form-control @error('status') is-invalid @enderror" @error('status') aria-describedby="status-error" aria-invalid="true" @enderror>

        @foreach(\App\Enums\TaskStatus::cases() as $option)
            <option value="{{ $option->value }}" @selected(old('status', $task->status?->value) === $option->value)>{{ $option->label() }}</option>
        @endforeach

    </select>

    @error('status')
        <div id="status-error" class="invalid-feedback">{{ $message }}</div>
    @enderror

</div>
<div class="d-flex align-items-center mt-4 pt-3 border-top">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button><a href="{{ route('tasks.index') }}" class="btn btn-link text-secondary ml-2">Cancel</a>
</div>


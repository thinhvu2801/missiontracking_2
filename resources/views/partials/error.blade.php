@if ($errors->any())
    <div class="alert alert-danger">
        <strong>❌ Có lỗi xảy ra:</strong>
        <ul style="margin-top: 10px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
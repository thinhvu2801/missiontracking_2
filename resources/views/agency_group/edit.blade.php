{{-- resources/views/agency_group/edit.blade.php (FORM ONLY - no layout) --}}
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cập nhật nhóm cơ quan</title>

  <link href="{{ asset('backend/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('backend/font-awesome/css/font-awesome.css') }}" rel="stylesheet">

  <style>
    :root{--bg:#f6f8fb;--card:#fff;--text:#0f172a;--muted:#64748b;--line:rgba(15,23,42,.10);--accent:#2563eb;--accent2:#06b6d4;--shadow:0 14px 34px rgba(2,6,23,.08);}
    html,body{height:100%}
    body{background:linear-gradient(180deg,#fbfdff,var(--bg));color:var(--text)}
    .wrap{min-height:100%;display:flex;justify-content:center;padding:22px}
    .cardx{width:100%;max-width:720px;background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
    .head{padding:16px 18px;border-bottom:1px solid rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;gap:12px}
    .title{display:flex;align-items:center;gap:10px;min-width:0}
    .icon{width:38px;height:38px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, rgba(37,99,235,.14), rgba(6,182,212,.14));border:1px solid rgba(37,99,235,.18);color:#1d4ed8;}
    .title h3{margin:0;font-weight:900;font-size:18px;letter-spacing:.2px}
    .title p{margin:2px 0 0;color:var(--muted);font-weight:700;font-size:12px}
    .body{padding:18px}
    label{font-weight:800}
    .form-control{border-radius:14px;border:1px solid rgba(15,23,42,.14);box-shadow:none}
    .form-control:focus{border-color:rgba(37,99,235,.45);box-shadow:0 0 0 3px rgba(37,99,235,.14)}
    .actions{display:flex;justify-content:flex-end;gap:10px;padding:14px 18px;border-top:1px solid rgba(15,23,42,.08);background:#fff}
    .btnx{border-radius:999px;padding:10px 14px;font-weight:900;border:1px solid rgba(15,23,42,.12);background:#fff;color:var(--text)}
    .btnx:hover{background:#f8fafc}
    .btnx.primary{background:rgba(37,99,235,.10);border-color:rgba(37,99,235,.22);color:#1d4ed8}
    .btnx.primary:hover{background:rgba(37,99,235,.14)}
    .chip{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(15,23,42,.04);border:1px solid rgba(15,23,42,.08);color:var(--muted);font-weight:800;font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="cardx">
      <div class="head">
        <div class="title">
          <div class="icon"><i class="fa fa-folder-open"></i></div>
          <div>
            <h3>Cập nhật nhóm cơ quan</h3>
            <p>Chỉnh sửa nhóm và lưu để cập nhật cây thư mục.</p>
          </div>
        </div>
        <span class="chip"><i class="fa fa-info-circle"></i> Form nhập</span>
      </div>

      <div class="body">
        @include('partials.error')

        <form method="POST" action="{{ route('agency-groups.update', $agencyGroup) }}">
          @csrf
          @method('PUT')
          <input type="hidden" name="modal" value="{{ request('modal') ? 1 : 0 }}">

          <div class="form-group">
            <label for="group_name">Tên nhóm <span class="text-danger">*</span></label>
            <input id="group_name" name="group_name" class="form-control" required
                   value="{{ old('group_name', $agencyGroup->group_name) }}" placeholder="Nhập tên nhóm">
          </div>

          <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" class="form-control" rows="3"
                      placeholder="Ghi chú ngắn (không bắt buộc)">{{ old('description', $agencyGroup->description) }}</textarea>
          </div>

          <div class="actions">
            <button type="button" class="btnx" onclick="tryClose()">Đóng</button>
            <button type="submit" class="btnx primary"><i class="fa fa-save"></i> Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="{{ asset('backend/js/jquery-3.1.1.min.js') }}"></script>
  <script src="{{ asset('backend/js/bootstrap.min.js') }}"></script>
  <script>
    function tryClose(){
      try { if (window.parent && window.parent.$) { window.parent.$('#crudModal').modal('hide'); return; } } catch(e){}
      window.close(); history.back();
    }
  </script>
</body>
</html>

{{-- resources/views/agency/create.blade.php (FORM ONLY - no layout) --}}
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="{{ asset('backend/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('backend/font-awesome/css/font-awesome.css') }}" rel="stylesheet">
  <link href="{{ asset('backend/css/plugins/select2/select2.min.css') }}" rel="stylesheet">

  <style>
    :root {
      --bg: #f6f8fb;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --line: rgba(15, 23, 42, .10);
      --accent: #2563eb;
      --accent2: #06b6d4;
      --shadow: 0 14px 34px rgba(2, 6, 23, .08);
    }

    html,
    body {
      height: 100%
    }

    body {
      background: linear-gradient(180deg, #fbfdff, var(--bg));
      color: var(--text)
    }

    .wrap {
      min-height: 100%;
      display: flex;
      justify-content: center;
      padding: 22px
    }

    .cardx {
      width: 100%;
      max-width: 820px;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: var(--shadow);
      overflow: hidden
    }

    .head {
      padding: 16px 18px;
      border-bottom: 1px solid rgba(15, 23, 42, .08);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px
    }

    .title {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0
    }

    .icon {
      width: 38px;
      height: 38px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(37, 99, 235, .14), rgba(6, 182, 212, .14));
      border: 1px solid rgba(37, 99, 235, .18);
      color: #1d4ed8;
    }

    .title h3 {
      margin: 0;
      font-weight: 900;
      font-size: 18px;
      letter-spacing: .2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis
    }

    .title p {
      margin: 2px 0 0;
      color: var(--muted);
      font-weight: 700;
      font-size: 12px
    }

    .body {
      padding: 18px
    }

    label {
      font-weight: 800
    }

    .help {
      color: var(--muted);
      font-weight: 700;
      font-size: 12px;
      margin-top: 6px
    }

    .form-control {
      border-radius: 14px;
      border: 1px solid rgba(15, 23, 42, .14);
      box-shadow: none;
      height: 44px
    }

    .form-control:focus {
      border-color: rgba(37, 99, 235, .45);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, .14)
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 14px 18px;
      border-top: 1px solid rgba(15, 23, 42, .08);
      background: #fff
    }

    .btnx {
      border-radius: 999px;
      padding: 10px 14px;
      font-weight: 900;
      border: 1px solid rgba(15, 23, 42, .12);
      background: #fff;
      color: var(--text)
    }

    .btnx:hover {
      background: #f8fafc
    }

    .btnx.primary {
      background: rgba(37, 99, 235, .10);
      border-color: rgba(37, 99, 235, .22);
      color: #1d4ed8
    }

    .btnx.primary:hover {
      background: rgba(37, 99, 235, .14)
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(15, 23, 42, .04);
      border: 1px solid rgba(15, 23, 42, .08);
      color: var(--muted);
      font-weight: 800;
      font-size: 12px
    }
  </style>
</head>

@php $user = auth()->user(); @endphp

<body>
  <div class="wrap">
    <div class="cardx">
      <div class="head">
        <div class="title">
          <div class="icon"><i class="fa fa-building"></i></div>
          <div style="min-width:0">
            <h3>Thêm cơ quan</h3>
            <p>Nhập thông tin cơ quan và lưu để thêm cơ quan.</p>
          </div>
        </div>
        <span class="chip"><i class="fa fa-info-circle"></i> Form nhập</span>
      </div>

      <div class="body">
        @include('partials.error')

        <form method="POST" action="{{ route('agencies.store') }}">
          @csrf
          <input type="hidden" name="modal" value="{{ request('modal') ? 1 : 0 }}">

          {{-- Nhóm cơ quan --}}
          <div class="form-group">
            <label>Nhóm cơ quan <span class="text-danger">*</span></label>

            @if($user->hasRole(['admin', 'supervisor']))
              <select name="agency_group_id" id="agency_group_id" class="form-control select2" required>
                <option value="">-- Chọn nhóm --</option>
                @foreach($groups as $group)
                  <option value="{{ $group->id }}" @selected((string) old('agency_group_id') === (string) $group->id)>
                    {{ $group->group_name }}
                  </option>
                @endforeach
              </select>
              <div class="help">Cơ quan sẽ nằm trong nhóm này trên cây thư mục.</div>
            @else
              {{-- sub_admin: tự động lấy nhóm của account hiện tại --}}
              <input type="text" class="form-control" value="{{ $groups->first()?->group_name }}" readonly>
              <input type="hidden" name="agency_group_id" value="{{ $groups->first()?->id }}">
            @endif
          </div>

          {{-- Cơ quan trực thuộc --}}
          <div class="form-group">
            <label>Cơ quan trực thuộc</label>

            @if($user->hasRole(['admin', 'supervisor']))
              <select name="parent_agency_id" id="parent_agency_id" class="form-control select2">
                <option value="">-- Không --</option>
              </select>
              <div class="help">Chọn cơ quan cha (nếu cơ quan này thuộc một cơ quan khác).</div>
            @else
              {{-- sub_admin: tự động lấy cơ quan của account hiện tại --}}
              <input type="text" class="form-control" value="{{ $parents->first()?->agency_name }}" readonly>
              <input type="hidden" name="parent_agency_id" value="{{ $parents->first()?->id }}">
            @endif
          </div>

          {{-- Tên cơ quan --}}
          <div class="form-group" style="margin-top:14px">
            <label for="agency_name">Tên cơ quan <span class="text-danger">*</span></label>
            <input id="agency_name" name="agency_name" class="form-control" required value="{{ old('agency_name') }}"
              placeholder="Ví dụ: Phòng Kế hoạch – Tài chính">
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
  <script src="{{ asset('backend/js/plugins/select2/select2.min.js') }}"></script>

  <script>
    function tryClose() {
      try { if (window.parent && window.parent.$) { window.parent.$('#crudModal').modal('hide'); return; } } catch (e) { }
      window.close(); history.back();
    }
    (function () {
      if (window.jQuery && $.fn.select2) {
        $('.select2').select2({ width: '100%' });
      }
    })();
  </script>
  
  <script>
  (function () {

    const $group  = $('#agency_group_id');
    const $parent = $('#parent_agency_id');

    function resetParent() {
      $parent.html('<option value="">-- Không --</option>');
      $parent.val(null).trigger('change');
    }

    function loadParents(groupId, selectedId = null) {
      if (!groupId) {
        resetParent();
        return;
      }

      $.get("{{ url('/ajax/agencies/by-group') }}/" + groupId)
        .done(function (data) {

          if (!Array.isArray(data)) {
            console.error('byGroup must return array', data);
            return;
          }

          resetParent();

          data.forEach(item => {
            $parent.append(new Option(item.agency_name, item.id));
          });

          if (selectedId) {
            $parent.val(String(selectedId)).trigger('change');
          }
        })
        .fail(function (xhr) {
          console.error('AJAX error', xhr.responseText);
        });
    }

    // change group
    $group.on('change', function () {
      loadParents(this.value);
    });

  })();
  </script>
</body>

</html>
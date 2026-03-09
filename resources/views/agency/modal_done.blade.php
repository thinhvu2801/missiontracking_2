<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Done</title>
</head>
<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; padding:16px;">
  <div style="color:#0f172a;font-weight:800;margin-bottom:8px;">{{ $message ?? 'Hoàn tất' }}</div>
  <div style="color:#64748b;">Đang cập nhật danh sách...</div>

  <script>
    (function(){
      try{
        if (window.parent && window.parent !== window) {
          window.parent.postMessage({type:'agency-crud-done'}, '*');
        }
      }catch(e){}
    })();
  </script>
</body>
</html>

<button type="button" onclick="confirmDelete(event)" style="border:none;background:none">
    <div class="text-danger"><i class="fa fa-trash fa-lg"></i></div>
</button>

<script>
    function confirmDelete(event) {
        event.preventDefault();
        
        Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            text: 'Bạn có chắc chắn muốn xóa bản ghi này?',
            showCancelButton: true,
            confirmButtonColor: '#ed5565',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form nếu button nằm trong form
                event.target.closest('form')?.submit();
            }
        });
    }
</script>
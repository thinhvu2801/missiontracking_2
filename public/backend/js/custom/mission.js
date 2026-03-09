$(function () {
    $('.select2').select2({
        width: '100%',
        placeholder: '-- Không --',
        allowClear: true
    });
});

$(function () {

    // Check cả nhóm
    $('.check-group').on('change', function () {
        const groupId = $(this).data('group');
        $('.check-agency[data-group="' + groupId + '"]')
            .prop('checked', this.checked);
    });

    // Check từng agency → cập nhật checkbox nhóm
    $('.check-agency').on('change', function () {
        const groupId = $(this).data('group');
        const all     = $('.check-agency[data-group="' + groupId + '"]');
        const checked = all.filter(':checked');

        $('.check-group[data-group="' + groupId + '"]')
            .prop('checked', checked.length === all.length);
    });

    // Load trạng thái ban đầu
    $('.check-group').each(function () {
        const groupId = $(this).data('group');
        const all     = $('.check-agency[data-group="' + groupId + '"]');
        const checked = all.filter(':checked');

        $(this).prop('checked', checked.length === all.length);
    });

});

$(function () {
    $('#search-agency').on('keyup', function () {
        const keyword = $(this).val().toLowerCase();

        $('.agency-group').each(function () {
            const groupName = $(this).data('name');
            let groupMatched  = groupName.includes(keyword);
            let agencyMatched = false;

            $(this).find('.agency-item').each(function () {
                const agencyName = $(this).data('name');
                const match = agencyName.includes(keyword);
                $(this).toggle(match);
                if (match) agencyMatched = true;
            });

            $(this).toggle(groupMatched || agencyMatched);
        });
    });
});

$(function () {

    const $groupSelect  = $('select[name="mission_group_id"]');
    const $parentSelect = $('#parent-mission');

    function loadParents(groupId, selectedId = null) {

        $parentSelect.html('<option value="">-- Không --</option>');

        if (!groupId) return;

        $.get(parentMissionUrl, { mission_group_id: groupId }, function (res) {

            res.forEach(function (item) {
                let selected = selectedId == item.id ? 'selected' : '';
                $parentSelect.append(
                    `<option value="${item.id}" ${selected}>${item.mission_name}</option>`
                );
            });

            $parentSelect.trigger('change.select2');
        });
    }

    // Khi đổi nhóm nhiệm vụ
    $groupSelect.on('change', function () {
        loadParents($(this).val(), null);
    });

    // Load ban đầu (edit)
    const initialGroupId  = $groupSelect.val();
    const initialParentId = (typeof currentParentMissionId !== 'undefined')
        ? currentParentMissionId
        : null;

    if (initialGroupId) {
        loadParents(initialGroupId, initialParentId);
    }

});

function toggleDeadline() {
    const missionType = $('input[name="mission_type"]:checked').val();
    const $deadlineInput = $('#deadline-input');
    const $deadlineWrapper = $('#deadline-wrapper');
    const $deadlineRequired = $('#deadline-required');

    if (missionType == 'time_limited') {
        $deadlineInput.prop('required', true);
        $deadlineRequired.removeClass('d-none');
        $deadlineWrapper.slideDown();
    } else {
        $deadlineInput.prop('required', false).val('');
        $deadlineRequired.addClass('d-none');
        $deadlineWrapper.slideUp();
    }
}

$(function () {
    toggleDeadline(); // load lần đầu (old input)
    $('input[name="mission_type"]').on('change', toggleDeadline);
});

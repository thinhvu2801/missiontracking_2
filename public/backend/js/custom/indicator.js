function toggleIndicatorType() {
    const type = $('input[name="indicator_type"]:checked').val();
    if (type === 'quantitative') {
        $('#quantitative-fields').slideDown();
    } else {
        $('#quantitative-fields').slideUp();
    }
}

$(function () {
    $('.select2').select2({
        width: '100%',
        placeholder: '-- Không --',
        allowClear: true
    });

    toggleIndicatorType();
    $('input[name="indicator_type"]').on('change', toggleIndicatorType);
});

$(function () {
    $('.check-group').on('change', function () {
        const groupId = $(this).data('group');
        $('.check-agency[data-group="' + groupId + '"]')
            .prop('checked', this.checked);
    });

    $('.check-agency').on('change', function () {
        const groupId = $(this).data('group');
        const all = $('.check-agency[data-group="' + groupId + '"]');
        const checked = all.filter(':checked');

        $('.check-group[data-group="' + groupId + '"]')
            .prop('checked', checked.length === all.length);
    });

    $('.check-group').each(function () {
        const groupId = $(this).data('group');
        const all = $('.check-agency[data-group="' + groupId + '"]');
        const checked = all.filter(':checked');
        $(this).prop('checked', checked.length === all.length);
    });

    $('#search-agency').on('keyup', function () {
        const keyword = $(this).val().toLowerCase();

        $('.agency-group').each(function () {
            const groupName = $(this).data('name');
            let groupMatched = groupName.includes(keyword);
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

    const $groupSelect  = $('select[name="indicator_group_id"]');
    const $parentSelect = $('#parent-indicator');

    function loadParents(groupId, selectedId = null) {

        $parentSelect.html('<option value="">-- Không --</option>');

        if (!groupId) return;

        $.get(parentIndicatorUrl, { indicator_group_id: groupId }, function (res) {

            res.forEach(function (item) {
                let selected = selectedId == item.id ? 'selected' : '';
                $parentSelect.append(
                    `<option value="${item.id}" ${selected}>${item.indicator_name}</option>`
                );
            });

            $parentSelect.trigger('change.select2');
        });
    }

    $groupSelect.on('change', function () {
        loadParents($(this).val(), null);
    });

    const initialGroupId = $groupSelect.val();
    const initialParentId = (typeof currentParentIndicatorId !== 'undefined')
        ? currentParentIndicatorId
        : null;

    if (initialGroupId) {
        loadParents(initialGroupId, initialParentId);
    }
});

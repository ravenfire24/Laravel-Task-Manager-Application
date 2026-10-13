/* Bootstrap supplies the modal's focus management and keyboard controls. */
(function ($) {
    'use strict';

    $('#deleteTaskModal').on('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        document.getElementById('deleteTaskName').textContent = trigger.getAttribute('data-task-title');
        document.getElementById('deleteTaskForm').action = trigger.getAttribute('data-delete-url');
    });

    var list = document.getElementById('taskOrderList');
    if (!list || typeof Sortable === 'undefined' || list.querySelectorAll('.task-row').length < 2) {
        return;
    }

    var message = document.getElementById('orderMessage');
    var handles = list.querySelectorAll('.drag-handle');
    var saving = false;
    var sortable = Sortable.create(list, {
        handle: '.drag-handle',
        draggable: '.task-row',
        animation: 150,
        ghostClass: 'task-drag-placeholder',
        onEnd: function (event) {
            if (event.oldIndex !== event.newIndex) {
                saveOrder();
            }
        }
    });
    var savedOrder = sortable.toArray();

    document.getElementById('orderHelp').hidden = false;
    handles.forEach(function (handle) {
        handle.hidden = false;
    });

    function announce(text, failed) {
        message.textContent = text;
        message.classList.toggle('text-danger', failed);
        message.classList.toggle('text-muted', !failed);
    }

    function saveOrder() {
        saving = true;
        sortable.option('disabled', true);
        list.setAttribute('aria-busy', 'true');
        announce('Saving task order...', false);

        $.ajax({
            url: list.getAttribute('data-order-url'),
            method: 'PATCH',
            contentType: 'application/json',
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            data: JSON.stringify({ids: sortable.toArray()}),
            timeout: 15000
        }).done(function () {
            savedOrder = sortable.toArray();
            announce('Task order saved.', false);
        }).fail(function (response) {
            sortable.sort(savedOrder);
            announce(response.status === 419
                ? 'Your session expired. Refresh the page before reordering.'
                : 'Could not confirm the new order. Refresh the page and try again.', true);
        }).always(function () {
            saving = false;
            sortable.option('disabled', false);
            list.removeAttribute('aria-busy');
        });
    }

    list.addEventListener('keydown', function (event) {
        var handle = event.target.closest('.drag-handle');
        if (!handle || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) {
            return;
        }
        event.preventDefault();
        if (saving) {
            return;
        }

        var row = handle.closest('.task-row');
        var neighbor = event.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
        if (!neighbor) {
            return;
        }
        list.insertBefore(row, event.key === 'ArrowUp' ? neighbor : neighbor.nextElementSibling);
        handle.focus();
        saveOrder();
    });
})(jQuery);

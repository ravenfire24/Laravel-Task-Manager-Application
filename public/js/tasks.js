/* Bootstrap supplies the modal's focus management and keyboard controls. */
(function ($) {
    'use strict';
    $('#deleteTaskModal').on('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        document.getElementById('deleteTaskName').textContent = trigger.getAttribute('data-task-title');
        document.getElementById('deleteTaskForm').action = trigger.getAttribute('data-delete-url');
    });
})(jQuery);

<div class="modal fade" id="deleteTaskModal" tabindex="-1" role="dialog" aria-labelledby="deleteTaskTitle" aria-describedby="deleteTaskDescription" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="deleteTaskTitle">Delete task?</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteTaskDescription" class="mb-0">The task <strong id="deleteTaskName"></strong> will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Keep task</button>
                <form method="post" id="deleteTaskForm">
                    @csrf
                    @method('DELETE')<button type="submit" class="btn btn-danger">Delete task</button>
                </form>
            </div>
        </div>
    </div>
</div>


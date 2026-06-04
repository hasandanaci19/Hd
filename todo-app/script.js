// To-Do List Application with Local Storage
class TodoApp {
    constructor() {
        this.todos = this.loadTodos();
        this.currentFilter = 'all';
        this.editingId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.render();
    }

    setupEventListeners() {
        // Input and Add button
        const todoInput = document.getElementById('todoInput');
        const addBtn = document.getElementById('addBtn');
        
        addBtn.addEventListener('click', () => this.addTodo());
        todoInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.addTodo();
        });

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentFilter = e.target.dataset.filter;
                this.render();
            });
        });

        // Action buttons
        document.getElementById('clearCompleted').addEventListener('click', () => this.clearCompleted());
        document.getElementById('clearAll').addEventListener('click', () => this.clearAll());
        document.getElementById('exportBtn').addEventListener('click', () => this.exportTodos());
    }

    addTodo() {
        const input = document.getElementById('todoInput');
        const text = input.value.trim();

        if (!text) {
            alert('Please enter a task!');
            return;
        }

        const todo = {
            id: Date.now(),
            text: text,
            completed: false,
            createdAt: new Date().toLocaleString()
        };

        this.todos.unshift(todo);
        this.saveTodos();
        input.value = '';
        this.render();
    }

    toggleTodo(id) {
        const todo = this.todos.find(t => t.id === id);
        if (todo) {
            todo.completed = !todo.completed;
            this.saveTodos();
            this.render();
        }
    }

    deleteTodo(id) {
        this.todos = this.todos.filter(t => t.id !== id);
        this.saveTodos();
        this.render();
    }

    editTodo(id) {
        this.editingId = id;
        const todo = this.todos.find(t => t.id === id);
        if (todo) {
            this.render();
            // Focus on edit input
            setTimeout(() => {
                const editInput = document.querySelector(`#edit-${id}`);
                if (editInput) editInput.focus();
            }, 0);
        }
    }

    saveTodoEdit(id, newText) {
        const text = newText.trim();
        if (!text) {
            alert('Task cannot be empty!');
            return;
        }

        const todo = this.todos.find(t => t.id === id);
        if (todo) {
            todo.text = text;
            this.saveTodos();
        }

        this.editingId = null;
        this.render();
    }

    cancelEdit() {
        this.editingId = null;
        this.render();
    }

    getFilteredTodos() {
        switch (this.currentFilter) {
            case 'active':
                return this.todos.filter(t => !t.completed);
            case 'completed':
                return this.todos.filter(t => t.completed);
            default:
                return this.todos;
        }
    }

    clearCompleted() {
        if (confirm('Delete all completed tasks?')) {
            this.todos = this.todos.filter(t => !t.completed);
            this.saveTodos();
            this.render();
        }
    }

    clearAll() {
        if (confirm('Delete ALL tasks? This cannot be undone!')) {
            this.todos = [];
            this.saveTodos();
            this.render();
        }
    }

    exportTodos() {
        const dataStr = JSON.stringify(this.todos, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `todos-${new Date().getTime()}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    updateStats() {
        const total = this.todos.length;
        const active = this.todos.filter(t => !t.completed).length;
        const completed = this.todos.filter(t => t.completed).length;

        document.getElementById('totalCount').textContent = total;
        document.getElementById('activeCount').textContent = active;
        document.getElementById('completedCount').textContent = completed;
    }

    render() {
        this.updateStats();
        const todoList = document.getElementById('todoList');
        const emptyState = document.getElementById('emptyState');
        const filteredTodos = this.getFilteredTodos();

        if (filteredTodos.length === 0) {
            todoList.innerHTML = '';
            emptyState.classList.add('show');
            return;
        }

        emptyState.classList.remove('show');
        
        todoList.innerHTML = filteredTodos.map(todo => `
            <li class="todo-item ${todo.completed ? 'completed' : ''}">
                ${this.editingId === todo.id ? `
                    <div class="edit-form">
                        <input 
                            type="text" 
                            id="edit-${todo.id}" 
                            class="edit-input" 
                            value="${this.escapeHtml(todo.text)}"
                        >
                        <button class="save-btn" onclick="app.saveTodoEdit(${todo.id}, document.getElementById('edit-${todo.id}').value)">Save</button>
                        <button class="cancel-btn" onclick="app.cancelEdit()">Cancel</button>
                    </div>
                ` : `
                    <input 
                        type="checkbox" 
                        class="checkbox" 
                        ${todo.completed ? 'checked' : ''}
                        onchange="app.toggleTodo(${todo.id})"
                    >
                    <span class="todo-text">${this.escapeHtml(todo.text)}</span>
                    <span class="todo-date">${todo.createdAt}</span>
                    <div class="todo-actions">
                        <button class="edit-btn" onclick="app.editTodo(${todo.id})">Edit</button>
                        <button class="delete-btn" onclick="app.deleteTodo(${todo.id})">Delete</button>
                    </div>
                `}
            </li>
        `).join('');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    saveTodos() {
        localStorage.setItem('todos', JSON.stringify(this.todos));
    }

    loadTodos() {
        const stored = localStorage.getItem('todos');
        return stored ? JSON.parse(stored) : [];
    }
}

// Initialize app when DOM is ready
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new TodoApp();
});

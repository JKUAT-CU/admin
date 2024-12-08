// script.js

function createBudget() {
    alert("Creating a new budget...");
}

function viewBudgets() {
    alert("Viewing all budgets...");
}

document.querySelectorAll('.btn-primary').forEach(button => {
    button.addEventListener('click', function () {
        alert("Functionality is under development!");
    });
});

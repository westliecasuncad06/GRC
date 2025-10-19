/* Add this CSS block right before the closing </style> tag */

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 350px;
}

.toast {
    background: white;
    color: var(--dark);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    border-left: 4px solid;
    width: 100%;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast.success {
    border-left-color: var(--success, #28a745);
    background: linear-gradient(to right, rgba(40, 167, 69, 0.05), transparent);
}

.toast.success i {
    color: var(--success, #28a745);
}

.toast.error {
    border-left-color: var(--danger, #dc3545);
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), transparent);
}

.toast.error i {
    color: var(--danger, #dc3545);
}

@media (max-width: 768px) {
    .toast-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    .toast {
        width: auto;
    }
}
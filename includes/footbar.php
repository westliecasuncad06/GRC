<!-- Footer Bar -->
<footer class="footbar">
    <div class="footbar-content">
        <div class="footbar-section">
            <span>
                © <?php echo date("Y"); ?> Global Reciprocal Colleges. All rights reserved.
            </span>
            <span class="footbar-links">
                <a href="https://web.facebook.com/OfficialGRC" target="_blank" rel="noopener noreferrer" aria-label="GRC Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 24 24">
                        <path d="M22.675 0h-21.35C.6 0 0 .6 0 1.325v21.351C0 23.4.6 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.894-4.788 4.659-4.788 1.325 0 2.466.099 2.797.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.313h3.59l-.467 3.622h-3.123V24h6.116C23.4 24 24 23.4 24 22.675V1.325C24 .6 23.4 0 22.675 0z"/>
                    </svg>
                    GRC Facebook
                </a> |
                <a href="https://grc.edu.ph/" target="_blank" rel="noopener noreferrer" aria-label="GRC Website">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 6.627 5.373 12 12 12s12-5.373 12-12c0-6.627-5.373-12-12-12zm0 22.5c-5.799 0-10.5-4.701-10.5-10.5S6.201 1.5 12 1.5 22.5 6.201 22.5 12 17.799 22.5 12 22.5z"/>
                        <path d="M12 5.25a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5zm0 12a5.25 5.25 0 1 1 0-10.5 5.25 5.25 0 0 1 0 10.5z"/>
                        <path d="M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5z"/>
                    </svg>
                    GRC Website
                </a>
            </span>
        </div>
        <div class="footbar-section footbar-credits">
            <span><strong>Developed by:</strong> Reciprocity</span>
            <span><strong>Full Stack Developer:</strong> Westlie R. Casuncad</span>
        </div>
    </div>
</footer>


<style>
.footbar {
    background-color: #F75270;
    color: white;
    padding: 1.5rem 2rem;
    text-align: center;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    position: relative;
    margin-top: 2rem;
    margin-left: 250px; /* Match sidebar width */
    width: calc(100% - 250px); /* Adjust width to account for sidebar */
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    float: right; /* Align with main content area */
    z-index: 100;
}

.footbar-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
    justify-content: center;
}

.footbar-section {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    justify-content: center;
}

.footbar-links {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
}

.footbar-credits {
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    padding-top: 0.75rem;
    font-size: 0.85rem;
}

.footbar-credits span {
    margin: 0 0.5rem;
}

.footbar a {
    color: white;
    text-decoration: underline;
    transition: color 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.footbar a:hover {
    color: #DC143C;
    text-decoration: none;
}

.footbar svg {
    vertical-align: middle;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .footbar {
        padding: 1rem;
        font-size: 0.8rem;
        margin-top: 1rem;
        margin-left: 0 !important; /* Remove sidebar margin on mobile */
        margin-bottom: 50px !important; /* Space for bottom navigation sidebar */
        width: 100% !important; /* Full width on mobile */
        float: none !important; /* Remove float on mobile */
        position: relative !important;
        z-index: 900 !important; /* Below sidebar but visible */
        clear: both;
        box-sizing: border-box;
    }

    .footbar-content {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }

    .footbar-section {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
    }

    .footbar-section span {
        display: block;
        width: 100%;
    }

    .footbar-links {
        flex-direction: column;
        gap: 0.5rem;
    }

    .footbar-credits {
        width: 100%;
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.3);
    }

    .footbar-credits span {
        display: block;
        margin: 0.25rem 0;
        line-height: 1.6;
    }
}

@media (max-width: 480px) {
    .footbar {
        padding: 0.75rem 0.5rem;
        font-size: 0.75rem;
        margin-bottom: 50px !important; /* Space for smaller bottom navigation */
    }

    .footbar-content {
        gap: 0.5rem;
    }

    .footbar-section {
        gap: 0.4rem;
    }

    .footbar-credits {
        font-size: 0.7rem;
        padding-top: 0.5rem;
    }

    .footbar-credits span {
        line-height: 1.5;
    }

    .footbar a {
        font-size: 0.75rem;
        line-height: 1.5;
    }

    .footbar svg {
        width: 14px;
        height: 14px;
    }
}

/* Ensure proper spacing for content */
@media (max-width: 768px) {
    body {
        padding-bottom: 0;
        overflow-x: hidden;
    }
}

</style>


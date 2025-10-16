<!-- Footer Bar -->
<footer class="footbar">
    <div class="footbar-content">

        <span>

            © <?php echo date("Y"); ?> Global Reciprocal Colleges. All rights reserved.
        </span>
        <span>
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
    <br>
    <br>
    <br>
    <br>
</footer>


<style>

.footbar {

    background-color: #F75270;

    color: white;

    padding: 1rem 2rem;

    text-align: center;

    font-family: 'Poppins', sans-serif;

    font-size: 0.9rem;

    position: relative;

    bottom: 0;

    width: 100%;

    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);

    margin-top: 2rem;

}

.footbar-content {

    display: flex;

    flex-wrap: wrap;

    justify-content: space-between;

    gap: 1rem;

    align-items: center;

}

.footbar a {

    color: white;

    text-decoration: underline;

    transition: color 0.3s ease;

}

.footbar a:hover {

    color: #DC143C;

    text-decoration: none;

}

@media (max-width: 768px) {

    .footbar {

        padding: 1rem;

        font-size: 0.8rem;

    }

    .footbar-content {

        flex-direction: column;

        gap: 0.5rem;

        text-align: center;

    }

    .footbar-content span {

        display: block;

    }

}

@media (max-width: 480px) {

    .footbar {

        padding: 0.5rem;

        font-size: 0.7rem;

    }

    .footbar-content {

        gap: 0.25rem;

    }

}

</style>


<footer>
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> Jūsų Svetainė. Visos teisės saugomos.</p>
    </div>
</footer>

<style>
/* Poraštės stiliai */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background-color: #333;
    color: #fff;
    padding: 10px 0;
}

.footer-container {
    max-width: 800px; /* Sumažinome maksimalų plotį, kad poraštė būtų siauresnė */
    margin: 0 auto;
    text-align: center;
}

.footer-container p {
    margin: 0;
    font-size: 14px;
}

/* Pagrindinio turinio stiliai */
.main-content {
    padding-bottom: 60px; /* Pridedame apatinį tarpą, kad turinys neuždengtų poraštės */
}

</style>

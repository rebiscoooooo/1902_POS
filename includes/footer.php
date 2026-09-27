</main>
</div>
<script>
    lucide.createIcons();
    function updateClock() {
        const now = new Date();
        let hours = now.getHours(), minutes = now.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12; hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        const clockEl = document.getElementById('live-clock');
        if(clockEl) clockEl.textContent = hours + ':' + minutes + ' ' + ampm;
    }
    setInterval(updateClock, 1000); updateClock();
    const sidebar = document.getElementById('sidebar'), mobileMenuBtn = document.getElementById('mobile-menu-btn'), mobileOverlay = document.getElementById('mobile-overlay');
    if(mobileMenuBtn && sidebar && mobileOverlay) {
        mobileMenuBtn.addEventListener('click', () => { sidebar.classList.add('open'); mobileOverlay.classList.remove('hidden'); });
        mobileOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); mobileOverlay.classList.add('hidden'); });
    }
</script>
</body>
</html>
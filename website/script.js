function pollState() {
    setInterval(() => {
        fetch('api.php?action=get_state')
            .then(res => res.json())
            .then(data => {
                if (typeof window.updateUI === 'function') {
                    window.updateUI(data);
                }
            })
            .catch(err => console.error("Poll error:", err));
    }, 1000);
}

document.addEventListener("DOMContentLoaded", () => {
    pollState();
});

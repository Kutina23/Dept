function startCountdown(endTime) {
    const timer = setInterval(function() {
        const now = new Date().getTime();
        const end = new Date(endTime).getTime();
        const distance = end - now;

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("countdown").innerHTML = minutes + "m " + seconds + "s ";

        if (distance < 0) {
            clearInterval(timer);
            document.getElementById("countdown").innerHTML = "ELECTION ENDED";
            window.location.reload();
        }
    }, 1000);
} 
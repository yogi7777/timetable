document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.button');
    
    // Timer starten/stoppen
    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const deptId = button.dataset.id;
            const isRunning = button.classList.contains('running');
            fetch('timer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `dept_id=${deptId}&action=${isRunning ? 'stop' : 'start'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'started') {
                    button.classList.add('running');
                    startTimer(button, Date.now() / 1000);
                } else if (data.status === 'stopped') {
                    button.classList.remove('running');
                    button.querySelector('.timer-display').textContent = '00:00:00';
                }
            });
        });
        
        if (button.classList.contains('running')) {
            const startTs = parseInt(button.dataset.start);
            startTimer(button, startTs);
        }
    });

    // Timer-Anzeige
    function startTimer(button, startTs) {
        const display = button.querySelector('.timer-display');
        const interval = setInterval(() => {
            if (!button.classList.contains('running')) {
                clearInterval(interval);
                return;
            }
            const seconds = Math.floor(Date.now() / 1000 - startTs);
            display.textContent = new Date(seconds * 1000).toISOString().substr(11, 8);
        }, 1000);
    }

    // Drag-and-Drop
    const grid = document.getElementById('buttons');
    let dragged;

    buttons.forEach(button => {
        button.addEventListener('dragstart', () => dragged = button);
        button.addEventListener('dragover', e => e.preventDefault());
        button.addEventListener('drop', () => {
            grid.insertBefore(dragged, button);
            saveOrder();
        });
    });

    function saveOrder() {
        const order = Array.from(grid.querySelectorAll('.button')).map(btn => btn.dataset.id);
        console.log('Saving order:', order); // Debugging
        fetch('save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order=${JSON.stringify(order)}`
        }).then(response => response.text())
          .then(text => console.log('Server response:', text)); // Debugging
    }
});
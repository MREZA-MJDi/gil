
const refreshAccount = document.querySelector('[data-account-refresh]');
if (refreshAccount) {
    refreshAccount.addEventListener('click', () => window.location.reload());
}
const jalaliDates = document.querySelectorAll('[data-jalali-date]');
jalaliDates.forEach(node => {
    const date = new Date(node.dataset.jalaliDate);
    if (Number.isNaN(date.getTime())) return;
    const formatted = new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-arabext', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Tehran',
    }).format(date);
    node.textContent = formatted;
});

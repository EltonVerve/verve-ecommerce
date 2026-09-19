(() => {
  const sections = document.querySelectorAll('.report-section');
  const reset = () => {
    sections.forEach(section => section.classList.remove('report-print-hidden'));
    document.body.classList.remove('printing-single-report');
  };
  document.querySelectorAll('[data-print-report]').forEach(button => {
    button.addEventListener('click', () => {
      const selected = button.dataset.printReport;
      reset();
      document.body.classList.toggle('printing-single-report', selected !== 'all');
      sections.forEach(section => section.classList.toggle('report-print-hidden', selected !== 'all' && section.id !== `report-${selected}`));
      window.print();
    });
  });
  window.addEventListener('afterprint', reset);
})();

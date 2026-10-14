/**
 * Shree Laxmi Expenses - Frontend Interactivity
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 2. Fuel Auto-Calculation (Liters * Rate = Amount)
    const fuelLiters = document.getElementById('fuel_liters');
    const fuelRate = document.getElementById('fuel_rate');
    const amountInput = document.getElementById('amount');

    function calculateFuelTotal() {
        if (!fuelLiters || !fuelRate || !amountInput) return;
        const liters = parseFloat(fuelLiters.value) || 0;
        const rate = parseFloat(fuelRate.value) || 0;
        if (liters > 0 && rate > 0) {
            amountInput.value = (liters * rate).toFixed(2);
        }
    }

    if (fuelLiters && fuelRate) {
        fuelLiters.addEventListener('input', calculateFuelTotal);
        fuelRate.addEventListener('input', calculateFuelTotal);
    }

    // 3. Category Type Switcher (Income vs Expense in Add Transaction)
    const switchExpense = document.getElementById('switchExpense');
    const switchIncome = document.getElementById('switchIncome');
    const typeInput = document.getElementById('typeInput');
    const categorySelect = document.getElementById('category_id');
    const fuelFieldsSection = document.getElementById('fuelFieldsSection');

    function filterCategories(type) {
        if (!categorySelect) return;
        const options = categorySelect.querySelectorAll('option');
        let firstVisible = null;
        options.forEach(opt => {
            if (!opt.value) return; // Skip placeholder
            const optType = opt.getAttribute('data-type');
            if (optType === type) {
                opt.style.display = '';
                if (!firstVisible) firstVisible = opt.value;
            } else {
                opt.style.display = 'none';
            }
        });
        if (firstVisible && !categorySelect.value) {
            categorySelect.value = firstVisible;
        }
        checkFuelCategory();
    }

    function checkFuelCategory() {
        if (!categorySelect || !fuelFieldsSection) return;
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const catName = selectedOption ? selectedOption.text.toLowerCase() : '';
        if (catName.includes('diesel') || catName.includes('fuel')) {
            fuelFieldsSection.style.display = 'block';
        } else {
            fuelFieldsSection.style.display = 'none';
        }
    }

    if (switchExpense && switchIncome && typeInput) {
        switchExpense.addEventListener('click', () => {
            switchExpense.classList.add('active', 'switch-expense');
            switchIncome.classList.remove('active', 'switch-income');
            typeInput.value = 'expense';
            filterCategories('expense');
        });

        switchIncome.addEventListener('click', () => {
            switchIncome.classList.add('active', 'switch-income');
            switchExpense.classList.remove('active', 'switch-expense');
            typeInput.value = 'income';
            filterCategories('income');
        });

        if (categorySelect) {
            categorySelect.addEventListener('change', checkFuelCategory);
            // Initial run based on active
            filterCategories(typeInput.value || 'expense');
        }
    }

    // 4. Lorry Selection -> Auto-load Assigned Driver & Branch
    const lorrySelect = document.getElementById('lorry_id');
    const driverSelect = document.getElementById('driver_id');
    const branchSelect = document.getElementById('branch_id');

    if (lorrySelect && (driverSelect || branchSelect)) {
        lorrySelect.addEventListener('change', async function() {
            const lorryId = this.value;
            if (!lorryId) return;

            try {
                const res = await fetch(`api/get_lorry_driver.php?lorry_id=${lorryId}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        if (driverSelect && data.driver_id) {
                            driverSelect.value = data.driver_id;
                        }
                        if (branchSelect && data.branch_id) {
                            branchSelect.value = data.branch_id;
                        }
                    }
                }
            } catch (err) {
                console.error('Error fetching lorry driver details:', err);
            }
        });
    }

    // 5. PDF Export using html2pdf.js or Native Print
    window.downloadPDF = function(elementId, filename = 'document.pdf') {
        const element = document.getElementById(elementId);
        if (!element) return;

        if (typeof html2pdf !== 'undefined') {
            const opt = {
                margin:       10,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        } else {
            window.print();
        }
    };
});

// Modal helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    // Global loader container
    const loaderHtml = `
        <div class="loader-container">
            <div class="loader"></div>
        </div>
    `;
    
    // Add loader container to the body if it doesn't exist
    if (!document.querySelector('.loader-container')) {
        document.body.insertAdjacentHTML('beforeend', loaderHtml);
    }
    
    const globalLoader = document.querySelector('.loader-container');
    
    // Function to show global loader
    window.showLoader = function() {
        globalLoader.classList.add('active');
    };
    
    // Function to hide global loader
    window.hideLoader = function() {
        globalLoader.classList.remove('active');
    };
    
    // Add button loaders to all form submit buttons
    const addButtonLoaders = function() {
        const submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
        
        submitButtons.forEach(button => {
            // Skip if already processed
            if (button.classList.contains('btn-with-loader')) return;
            
            // Add loader classes
            button.classList.add('btn-with-loader');
            
            // Wrap text content in a span if it doesn't have one
            if (!button.querySelector('.button-text')) {
                const buttonText = button.innerHTML;
                button.innerHTML = `<span class="button-text">${buttonText}</span><span class="button-loader"></span>`;
            }
        });
    };
    
    // Process all forms to add loaders
    const setupFormLoaders = function() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Skip if already processed
            if (form.dataset.loaderSetup) return;
            form.dataset.loaderSetup = 'true';
            
            form.addEventListener('submit', function(e) {
                // Get the submit button
                const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitButton) {
                    // Add loading state to button
                    submitButton.classList.add('loading');
                    submitButton.disabled = true;
                }
                
                // For forms that might use AJAX, we'll handle in the AJAX section
                if (!form.dataset.ajax) {
                    // Show global loader for regular form submissions
                    showLoader();
                }
            });
        });
    };
    
    // Setup AJAX request interceptors for fetch API
    const originalFetch = window.fetch;
    window.fetch = function() {
        showLoader();
        return originalFetch.apply(this, arguments)
            .then(response => {
                hideLoader();
                return response;
            })
            .catch(error => {
                hideLoader();
                throw error;
            });
    };
    
    // Setup AJAX request interceptors for XMLHttpRequest
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function() {
        this._loaderShown = false;
        return originalXHROpen.apply(this, arguments);
    };
    
    XMLHttpRequest.prototype.send = function() {
        if (!this._loaderShown) {
            this._loaderShown = true;
            showLoader();
        }
        
        this.addEventListener('loadend', function() {
            hideLoader();
        });
        
        return originalXHRSend.apply(this, arguments);
    };
    
    // Initialize loaders
    addButtonLoaders();
    setupFormLoaders();
    
    // Re-initialize loaders when DOM changes (for dynamically added content)
    const observer = new MutationObserver(function(mutations) {
        addButtonLoaders();
        setupFormLoaders();
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
});
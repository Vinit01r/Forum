document.addEventListener('DOMContentLoaded', () => {
    // 1. Live search
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                searchResults.innerHTML = '';
                return;
            }
            
            timeout = setTimeout(() => {
                fetch(`search_ajax.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            searchResults.innerHTML = data.map(item => `
                                <a href="topic.php?id=${item.id}" class="search-result-item">
                                    <div style="font-weight: 500;">${item.title}</div>
                                    <div style="font-size: 0.75rem; color: var(--muted);">in ${item.category_name}</div>
                                </a>
                            `).join('');
                            searchResults.classList.add('active');
                        } else {
                            searchResults.innerHTML = '<div style="padding: 10px; color: var(--muted); text-align:center;">No results found.</div>';
                            searchResults.classList.add('active');
                        }
                    })
                    .catch(err => console.error('Search error:', err));
            }, 300);
        });
        
        // Hide search results on click outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    }

    // 2. Like button
    const likeBtns = document.querySelectorAll('.like-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    likeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (btn.hasAttribute('disabled')) return;
            
            const postId = btn.dataset.id;
            
            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const countSpan = btn.querySelector('.like-count');
                    const icon = btn.querySelector('i');
                    countSpan.textContent = data.count;
                    
                    if (data.liked) {
                        btn.style.border = '1px solid var(--danger)';
                        btn.style.color = 'var(--danger)';
                        btn.style.background = 'transparent';
                    } else {
                        btn.style.border = '1px solid transparent';
                        btn.style.color = 'white';
                        btn.style.background = 'var(--muted)';
                    }
                } else if (data.error) {
                    alert(data.error);
                }
            })
            .catch(err => console.error('Like error:', err));
        });
    });

    // 3. Reply form Quote
    const quoteBtns = document.querySelectorAll('.quote-btn');
    const replyContent = document.getElementById('replyContent');
    const replySection = document.getElementById('replySection');
    
    quoteBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const username = btn.dataset.username;
            const content = btn.dataset.content;
            
            if (replyContent) {
                const quoteText = `> @${username} said:\n> ${content}\n\n`;
                replyContent.value += quoteText;
                replyContent.focus();
                
                if (replySection) {
                    replySection.scrollIntoView({ behavior: 'smooth' });
                }
                
                // Trigger char count update
                const event = new Event('input');
                replyContent.dispatchEvent(event);
            }
        });
    });

    // 4. Character counter
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(ta => {
        const countDisplay = ta.nextElementSibling;
        if (countDisplay && countDisplay.id === 'charCount') {
            ta.addEventListener('input', () => {
                const len = ta.value.length;
                countDisplay.textContent = `${len} character${len !== 1 ? 's' : ''}`;
                if (len > 5000) {
                    countDisplay.style.color = 'var(--danger)';
                } else {
                    countDisplay.style.color = 'var(--muted)';
                }
            });
            // trigger initially
            const ev = new Event('input');
            ta.dispatchEvent(ev);
        }
    });

    // 5. Confirm dialogs for admin or any delete action
    const confirmForms = document.querySelectorAll('form.require-confirm');
    confirmForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Are you sure you want to proceed with this action? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // 6. Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease-out';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }, 4000);
    });

    // Report Modal
    const reportBtns = document.querySelectorAll('.report-btn');
    const reportModal = document.getElementById('reportModal');
    const closeReportModal = document.getElementById('closeReportModal');
    const submitReport = document.getElementById('submitReport');
    const reportPostId = document.getElementById('reportPostId');
    const reportReasonSelect = document.getElementById('reportReasonSelect');
    const reportDetails = document.getElementById('reportDetails');

    if (reportModal && reportBtns.length > 0) {
        reportBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                reportPostId.value = btn.dataset.postId;
                reportModal.classList.add('active');
            });
        });

        if (closeReportModal) {
            closeReportModal.addEventListener('click', () => {
                reportModal.classList.remove('active');
            });
        }

        if (submitReport) {
            submitReport.addEventListener('click', () => {
                const postId = reportPostId.value;
                const reason = reportReasonSelect.value;
                const details = reportDetails.value.trim();
                
                const fullReason = details ? `${reason}: ${details}` : reason;

                fetch('report_post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        post_id: postId,
                        reason: fullReason,
                        csrf_token: csrfToken
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Report submitted successfully. Thank you.');
                        reportModal.classList.remove('active');
                        reportDetails.value = '';
                    } else {
                        alert('Error submitting report: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => console.error(err));
            });
        }
    }
});

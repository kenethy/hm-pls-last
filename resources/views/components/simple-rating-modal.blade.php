<!-- Simple Rating Modal - Guaranteed to Work -->
<div id="simpleRatingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Rating Montir</h2>
            <button onclick="closeSimpleRatingModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>

        <!-- Service Info -->
        <div id="simpleServiceInfo" style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
            <div style="font-weight: 500; color: #374151; margin-bottom: 4px;" id="simpleServiceType">-</div>
            <div style="font-size: 14px; color: #6b7280;" id="simpleVehicleInfo">-</div>
        </div>

        <!-- Mechanics Container -->
        <div id="simpleMechanicsContainer">
            <!-- Mechanics will be inserted here -->
        </div>

        <!-- Footer -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
            <button onclick="closeSimpleRatingModal()" style="padding: 8px 16px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
                Tutup
            </button>
            <button onclick="submitAllSimpleRatings()" style="padding: 8px 16px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Kirim Semua Rating
            </button>
        </div>
    </div>
</div>

<!-- Mechanic Card Template -->
<template id="simpleMechanicTemplate">
    <div class="simple-mechanic-card" style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin-bottom: 16px;">
        <!-- Mechanic Info -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <div class="simple-mechanic-name" style="font-weight: 500; color: #111827; margin-bottom: 2px;"></div>
                <div class="simple-mechanic-spec" style="font-size: 12px; color: #6b7280;"></div>
            </div>
            <div class="simple-rating-status" style="font-size: 12px; padding: 4px 8px; background: #fef3c7; color: #92400e; border-radius: 4px;">
                Belum Rating
            </div>
        </div>

        <!-- Star Rating -->
        <div style="margin-bottom: 12px;">
            <div style="font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Berikan Rating:</div>
            <div class="simple-star-container" style="display: flex; gap: 4px;" data-mechanic-id="">
                <button class="simple-star" data-rating="1" onclick="setSimpleRating(this)" style="background: none; border: none; cursor: pointer; padding: 2px;">
                    <svg width="24" height="24" fill="#d1d5db" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
                <button class="simple-star" data-rating="2" onclick="setSimpleRating(this)" style="background: none; border: none; cursor: pointer; padding: 2px;">
                    <svg width="24" height="24" fill="#d1d5db" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
                <button class="simple-star" data-rating="3" onclick="setSimpleRating(this)" style="background: none; border: none; cursor: pointer; padding: 2px;">
                    <svg width="24" height="24" fill="#d1d5db" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
                <button class="simple-star" data-rating="4" onclick="setSimpleRating(this)" style="background: none; border: none; cursor: pointer; padding: 2px;">
                    <svg width="24" height="24" fill="#d1d5db" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
                <button class="simple-star" data-rating="5" onclick="setSimpleRating(this)" style="background: none; border: none; cursor: pointer; padding: 2px;">
                    <svg width="24" height="24" fill="#d1d5db" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Submit Button -->
        <button class="simple-submit-btn" onclick="submitSimpleRating(this)" disabled style="width: 100%; padding: 8px; background: #d1d5db; color: #6b7280; border: none; border-radius: 4px; cursor: not-allowed;">
            Kirim Rating
        </button>
    </div>
</template>

<script>
// Simple Rating System - Guaranteed to Work
let simpleRatings = {};
let currentSimpleServiceId = null;

function openSimpleRatingModal(serviceData) {
    console.log('🎯 Opening simple rating modal:', serviceData);
    
    currentSimpleServiceId = serviceData.service_id;
    
    // Set service info
    document.getElementById('simpleServiceType').textContent = serviceData.service_type || 'Servis';
    document.getElementById('simpleVehicleInfo').textContent = serviceData.vehicle_info || '-';
    
    // Clear previous mechanics
    document.getElementById('simpleMechanicsContainer').innerHTML = '';
    
    // Add mechanics
    serviceData.mechanics.forEach(mechanic => {
        addSimpleMechanic(mechanic);
    });
    
    // Show modal
    document.getElementById('simpleRatingModal').style.display = 'block';
    console.log('✅ Simple rating modal opened');
}

function addSimpleMechanic(mechanic) {
    const template = document.getElementById('simpleMechanicTemplate');
    const clone = template.content.cloneNode(true);
    
    // Set mechanic info
    clone.querySelector('.simple-mechanic-name').textContent = mechanic.name;
    clone.querySelector('.simple-mechanic-spec').textContent = mechanic.specialization || 'Montir Umum';
    clone.querySelector('.simple-star-container').setAttribute('data-mechanic-id', mechanic.id);
    clone.querySelector('.simple-submit-btn').setAttribute('data-mechanic-id', mechanic.id);
    
    document.getElementById('simpleMechanicsContainer').appendChild(clone);
}

function setSimpleRating(starButton) {
    const rating = parseInt(starButton.getAttribute('data-rating'));
    const container = starButton.closest('.simple-star-container');
    const mechanicId = container.getAttribute('data-mechanic-id');
    const card = starButton.closest('.simple-mechanic-card');
    
    console.log(`⭐ Setting rating ${rating} for mechanic ${mechanicId}`);
    
    // Store rating
    simpleRatings[mechanicId] = rating;
    
    // Update star visuals
    const stars = container.querySelectorAll('.simple-star svg');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.setAttribute('fill', '#f59e0b'); // Amber
        } else {
            star.setAttribute('fill', '#d1d5db'); // Gray
        }
    });
    
    // Update status
    const status = card.querySelector('.simple-rating-status');
    status.textContent = `${rating} bintang`;
    status.style.background = '#dcfce7';
    status.style.color = '#166534';
    
    // Enable submit button
    const submitBtn = card.querySelector('.simple-submit-btn');
    submitBtn.disabled = false;
    submitBtn.style.background = '#f59e0b';
    submitBtn.style.color = 'white';
    submitBtn.style.cursor = 'pointer';
}

function submitSimpleRating(button) {
    const mechanicId = button.getAttribute('data-mechanic-id');
    const rating = simpleRatings[mechanicId];
    
    if (!rating) {
        alert('Silakan pilih rating terlebih dahulu');
        return;
    }
    
    console.log(`📤 Submitting rating ${rating} for mechanic ${mechanicId}`);
    
    // Show loading
    button.textContent = 'Mengirim...';
    button.disabled = true;
    
    // Submit rating
    fetch('/api/ratings/submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            service_id: currentSimpleServiceId,
            mechanic_id: mechanicId,
            rating: rating,
            comment: null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.textContent = '✅ Berhasil';
            button.style.background = '#10b981';
            console.log('✅ Rating submitted successfully');
        } else {
            button.textContent = 'Gagal - Coba Lagi';
            button.style.background = '#ef4444';
            button.disabled = false;
            console.error('❌ Rating submission failed:', data.message);
        }
    })
    .catch(error => {
        button.textContent = 'Error - Coba Lagi';
        button.style.background = '#ef4444';
        button.disabled = false;
        console.error('❌ Rating submission error:', error);
    });
}

function submitAllSimpleRatings() {
    const mechanicCards = document.querySelectorAll('.simple-mechanic-card');
    let submitted = 0;
    
    mechanicCards.forEach(card => {
        const submitBtn = card.querySelector('.simple-submit-btn');
        const mechanicId = submitBtn.getAttribute('data-mechanic-id');
        
        if (simpleRatings[mechanicId] && !submitBtn.disabled) {
            submitSimpleRating(submitBtn);
            submitted++;
        }
    });
    
    if (submitted === 0) {
        alert('Tidak ada rating yang siap dikirim');
    } else {
        console.log(`📤 Submitting ${submitted} ratings`);
    }
}

function closeSimpleRatingModal() {
    document.getElementById('simpleRatingModal').style.display = 'none';
    simpleRatings = {};
    currentSimpleServiceId = null;
    console.log('🔒 Simple rating modal closed');
}
</script>

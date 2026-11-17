<footer class="footer" style="text-align: center; padding: 2rem 1rem; margin-top: 3rem; color: #64748b; font-size: 0.9rem; border-top: 1px solid #e2e8f0;">
    <p>&copy; <?= date('Y') ?> Clinic Appointment System. All rights reserved.</p>
</footer>

<?php if (!empty($load_booking_js)): ?>
    <script src="<?= $base_path ?>js/booking.js?v=3"></script>
<?php endif; ?>

</body>
</html>
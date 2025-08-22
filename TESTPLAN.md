# Test Plan

1. Activate the plugin and ensure no fatal errors.
2. Verify the `gffm_vendor` role exists after activation.
3. Edit a Vendor post:
   - Use the **Vendor Portal Access** meta box to enable access.
   - Send an invite email and confirm a user is created and linked.
4. Follow the magic link to `/vendor-portal/` and log in as the vendor, confirming the portal loads without fatal errors.
5. In the portal:
   - Update profile fields and confirm post meta saves.
   - Submit a weekly highlight and ensure only one post per week.
   - Confirm success notices appear without page reload.
6. Place `[gffm_this_week]` on a page and confirm highlights appear.
7. From **Vendor Assignment**, toggle a vendor's enable state and confirm the table reflects the change.
8. Uninstall plugin and confirm portal options are removed but posts remain.
9. Attempt to log in with a user lacking a linked vendor or disabled portal access and verify the error message appears.

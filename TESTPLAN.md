# Test Plan

1. Activate the plugin and ensure no fatal errors.
2. Verify the `gffm_vendor` role exists after activation.
3. Edit a Vendor post:
   - Add an email in the **Vendor Email** meta box.
   - Use the **Vendor Portal Access** meta box to enable access.
   - Send an invite email and confirm a user is created and linked.
   - Revoke access and ensure linkage is removed.
4. Follow the magic link to `/vendor-portal/` and log in as the vendor.
5. In the portal:
   - Update profile fields and confirm post meta saves.
   - Submit a weekly highlight; verify it is saved as pending unless auto-publish is enabled.
   - Resubmit within the same week and confirm the existing post updates.
6. Place `[gffm_this_week]` on a page and confirm highlights appear.
7. In **Vendor Assignment** screen, enable and disable selected vendors.
8. Uninstall plugin and confirm portal options are removed but posts remain.

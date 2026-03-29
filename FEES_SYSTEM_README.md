# Fees Management System - EduTrackr

## Overview

This is a complete rebuild of the Fees Management System for EduTrackr, designed to match real school fee workflows with installments, fee groups, extra charges, and student assignments.

## Database Setup

**IMPORTANT:** Before using the new fees system, you must run the database migration:

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
2. Run the SQL file: `database_fees_migration.sql`
3. This will create all necessary tables:
   - `fee_groups` - Fee categories (Tuition, Library, Bus, etc.)
   - `fee_installments` - Class-based installments
   - `extra_fees` - Additional fees per class or student
   - `student_fees` - Fees assigned to students
   - `fee_payments` - Payment records

## System Features

### 1. Fee Groups
- Create fee categories (Tuition Fee, Library Fee, Bus Fee, etc.)
- Each group can have a description
- Location: `superadmin/fees/manage_groups.php`

### 2. Fee Installments
- Create multiple installments per class
- Example: Class 10 can have Installment 1 (₹10,000, Due: April), Installment 2 (₹10,000, Due: July), etc.
- Location: `superadmin/fees/manage_installments.php`

### 3. Extra Fees
- Add extra fees that can be:
  - Assigned to entire class (applies to all students)
  - Assigned to specific students
- Examples: Library Fee, Bus Fee, ID Card Fee, Exam Fee
- Location: `superadmin/fees/manage_extra_fees.php`

### 4. Student Fee Assignment
- **Automatic Assignment**: When a student joins a class, all class installments and class-level extra fees are automatically assigned
- **Manual Assignment**: Admin can manually assign fees via Student Ledger
- Location: `superadmin/fees/student_ledger.php`

### 5. Payment Tracking
- Record payments (Paid, Partial, Pending)
- Track payment history
- Update fee status automatically
- Location: `superadmin/fees/payments.php`

### 6. Reports
- Class-wise fee reports
- Total due, collected, pending
- Collection percentage
- Location: `superadmin/fees/reports.php`

## User Access

### Super Admin
- Full access to all fee management features
- Can create fee groups, installments, extra fees
- Can view student ledgers, record payments, view reports
- Main dashboard: `superadmin/fees/index.php`

### Teacher
- **Read-only** access to student fees
- Can view fee status for students in their classes
- Cannot edit or record payments
- Location: `teacher/fees_view.php`

### Student
- View their own fees
- View installment schedule
- View payment history
- Cannot make payments (admin records payments)
- Location: `student/fees.php`

## File Structure

```
superadmin/fees/
├── index.php                    # Fees dashboard
├── manage_groups.php            # Manage fee groups
├── manage_installments.php      # Manage class installments
├── manage_extra_fees.php        # Manage extra fees
├── student_ledger.php           # View/manage student fees
├── payments.php                 # Record payments
└── reports.php                  # Fee reports

teacher/
└── fees_view.php                # View student fees (read-only)

student/
└── fees.php                     # View own fees

includes/
└── functions.php                # Helper functions (updated)

database_fees_migration.sql      # Database migration file
```

## Helper Functions

New functions added to `includes/functions.php`:

- `getAllFeeGroups()` - Get all fee groups
- `getFeeInstallmentsByClass($classId)` - Get installments for a class
- `getExtraFees($classId, $studentId)` - Get extra fees
- `getStudentFees($studentId)` - Get all fees for a student
- `assignInstallmentsToStudent($studentId, $classId)` - Auto-assign fees
- `getStudentPaymentHistory($studentId)` - Get payment history

## Workflow Example

1. **Admin creates fee groups**: Tuition Fee, Library Fee, Bus Fee
2. **Admin creates installments for Class 10**:
   - Installment 1: ₹10,000, Due: April 1
   - Installment 2: ₹10,000, Due: July 1
   - Installment 3: ₹10,000, Due: October 1
3. **Admin adds extra fees for Class 10**:
   - Library Fee: ₹1,200 (applies to all students)
   - Bus Fee: ₹3,000 (applies to all students)
4. **Student joins Class 10**: All installments and extra fees are automatically assigned
5. **Admin records payment**: Marks Installment 1 as Paid
6. **Student views fees**: Sees Installment 1 marked as Paid, others as Pending

## Notes

- The old `fees_structure` and `fees_payment` tables are kept for backward compatibility but are not used by the new system
- All new code uses prepared statements for security
- The UI follows the existing EduTrackr theme
- No breaking changes to other modules

## Support

If you encounter any issues:
1. Ensure the database migration has been run
2. Check that all foreign key relationships are correct
3. Verify that students have a `class_id` assigned
4. Check PHP error logs for any issues


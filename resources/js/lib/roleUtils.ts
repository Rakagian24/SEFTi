/**
 * Transforms the role label based on department
 * Changes "Kepala Toko" to "Brand Manager" for users from Human Greatness or Zi&Glo departments
 */
export function transformRoleLabel(role: string, department?: string): string {
  if (role === 'Kepala Toko' && (department === 'Human Greatness' || department === 'Zi&Glo')) {
    return 'Brand Manager';
  }
  return role;
}

/**
 * Transforms the role label for a user object
 * @param user User object with role and department properties
 * @returns The transformed role name
 */
export function transformUserRoleLabel(user: any): string {
  if (!user) return '';

  const roleName = user.role?.name || '';
  const departmentName = user.department?.name || '';

  return transformRoleLabel(roleName, departmentName);
}

/**
 * Helper function to transform role in approval steps
 * @param step Approval step object with role property
 * @param user User object with department property
 * @returns Step with transformed role if needed
 */
export function transformApprovalStepRole(step: any, user?: any): any {
  if (!step) return step;

  // If we have user info in the step itself
  if (step.completed_by && step.role === 'Kepala Toko') {
    const departmentName = step.completed_by.department?.name || '';
    if (departmentName === 'Human Greatness' || departmentName === 'Zi&Glo') {
      return { ...step, role: 'Brand Manager' };
    }
  }

  // If we have external user info
  if (user && step.role === 'Kepala Toko') {
    const departmentName = user.department?.name || '';
    if (departmentName === 'Human Greatness' || departmentName === 'Zi&Glo') {
      return { ...step, role: 'Brand Manager' };
    }
  }

  return step;
}

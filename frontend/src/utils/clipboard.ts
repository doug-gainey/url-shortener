/**
 * Copy text to clipboard with feedback
 * @param text - Text to copy
 * @param feedbackMs - Duration to show success message (default: 2000ms)
 * @returns Promise<boolean> - True if copy successful, false otherwise
 */
export async function copyToClipboard(
  text: string,
  feedbackMs: number = 2000,
): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch (err) {
    console.error("Failed to copy to clipboard:", err);
    return false;
  }
}

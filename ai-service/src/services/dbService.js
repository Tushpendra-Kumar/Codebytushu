const pool = require('../config/db');

class DBService {
  /**
   * Fetch context for courses (e.g. latest or matching query)
   */
  async getCoursesContext() {
    try {
      const [rows] = await pool.query(
        'SELECT title, slug, price, is_free FROM courses WHERE is_published = 1 ORDER BY id DESC LIMIT 5'
      );
      if (rows.length === 0) return 'No courses available currently.';
      
      let context = 'Available Courses:\n';
      rows.forEach(course => {
        const priceText = course.is_free ? 'Free' : `₹${course.price}`;
        context += `- ${course.title} (Price: ${priceText}) -> Link: [${course.title}](/courses/${course.slug})\n`;
      });
      return context;
    } catch (error) {
      console.error('Error fetching courses context:', error.message);
      return '';
    }
  }

  /**
   * Fetch context for latest LeetCode solutions
   */
  async getLeetCodeContext() {
    try {
      const [rows] = await pool.query(
        'SELECT problem_title, problem_number, difficulty, slug FROM leetcode_solutions WHERE is_published = 1 ORDER BY solution_date DESC LIMIT 5'
      );
      if (rows.length === 0) return 'No recent LeetCode solutions.';
      
      let context = 'Recent LeetCode Solutions:\n';
      rows.forEach(sol => {
        context += `- #${sol.problem_number} ${sol.problem_title} (${sol.difficulty}) -> Link: [View Solution](/Leetcode/solution/${sol.slug})\n`;
      });
      return context;
    } catch (error) {
      console.error('Error fetching leetcode context:', error.message);
      return '';
    }
  }

  /**
   * Fetch context for latest Blogs
   */
  async getBlogsContext() {
    try {
      const [rows] = await pool.query(
        'SELECT title, slug FROM blog_articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 5'
      );
      if (rows.length === 0) return 'No recent blogs available.';
      
      let context = 'Recent Blogs:\n';
      rows.forEach(blog => {
        context += `- ${blog.title} -> Link: [/blog/${blog.slug}](/blog/${blog.slug})\n`;
      });
      return context;
    } catch (error) {
      console.error('Error fetching blogs context:', error.message);
      return '';
    }
  }

  /**
   * Fetch a combined dynamic context string to inject into the AI prompt
   */
  async buildDynamicContext(urlContext = '') {
    let contextStr = '\n--- REAL-TIME DATABASE KNOWLEDGE ---\n';
    
    // Always attach basic course info
    const courses = await this.getCoursesContext();
    if (courses) contextStr += courses + '\n';

    // Always attach basic blogs info
    const blogs = await this.getBlogsContext();
    if (blogs) contextStr += blogs + '\n';

    // If user is on LeetCode page, fetch extra LeetCode context
    if (urlContext.toLowerCase().includes('/leetcode')) {
      const leetcode = await this.getLeetCodeContext();
      if (leetcode) contextStr += leetcode + '\n';
    }
    
    contextStr += '------------------------------------\n';
    return contextStr;
  }
}

module.exports = new DBService();
